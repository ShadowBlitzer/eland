<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = new util\app();

$app['route_class'] = 'util\route';

$app['debug'] = getenv('DEBUG') ? true : false;

\Symfony\Component\Debug\ErrorHandler::register();
\Symfony\Component\Debug\ExceptionHandler::register($app['debug']);

$app->register(new Predis\Silex\ClientServiceProvider(), [
	'predis.parameters' => getenv('REDIS_URL'),
	'predis.options'    => [
		'prefix'  => 'eland_',
	],
]);

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => [
        'url'   => getenv('DATABASE_URL'),
    ],
]);

$app->register(new Silex\Provider\TwigServiceProvider(), [
	'twig.path' => __DIR__ . '/view',
	'twig.options'	=> [
		'cache'		=> __DIR__ . '/cache',
		'debug'		=> getenv('DEBUG'),
	],
	'twig.form.templates'	=> [
		'bootstrap_3_layout.html.twig',
	],
]);

$app->extend('twig', function($twig, $app) {

	$twig->addExtension(new twig\extension());
	$twig->addRuntimeLoader(new \Twig_FactoryRuntimeLoader([
		twig\config::class => function() use ($app){
			return new twig\config($app['config']);
		},
		twig\distance::class => function() use ($app){
			return new twig\distance($app['db'], $app['cache']);
		},
		twig\date_format::class => function() use ($app){
			return new twig\date_format($app['config']);
		},
	]));
	$twig->addGlobal('s3_img', getenv('S3_IMG'));
	$twig->addGlobal('s3_doc', getenv('S3_DOC'));

	return $twig;
});

$app->register(new Silex\Provider\AssetServiceProvider(), [
    'assets.version' => '15',
    'assets.version_format' => '%s?v=%s',
    'assets.base_path' => '/assets',
    'assets.named_packages' => [
        'css' 		=> ['base_path' => '/assets/css', 'version' => '15', 'version_format' => '%s?v=%s'],
        'js'		=> ['base_path'	=> '/assets/js', 'version' => '15', 'version_format' => '%s?v=%s'],
        'loc_img'	=> ['base_path'	=> '/assets/img', 'version' => '15', 'version_format' => '%s?v=%s'],
        'img' 		=> ['base_urls' => ['http://' . getenv('S3_IMG')]],
        'doc'		=> ['base_urls' => ['http://' . getenv('S3_DOC')]],
        'maxcdn'	=> ['base_urls' => ['https://maxcdn.bootstrapcdn.com']],
        'cdnjs'		=> ['base_urls'	=> ['https://cdnjs.cloudflare.com/ajax/libs']],
        'jquery'	=> ['base_urls'	=> ['https://code.jquery.com']],
    ],
]);

$app->register(new Silex\Provider\LocaleServiceProvider());

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => ['nl', 'en'],
    'locale'			=> 'nl',
));

/*
 * The locale must be installed in the OS for formatting dates.
 */

setlocale(LC_TIME, 'nl_NL.UTF-8');

date_default_timezone_set((getenv('TIMEZONE')) ?: 'Europe/Brussels');

use Symfony\Component\Translation\Loader\YamlFileLoader;

$app->extend('translator', function($translator, $app) {

	$translator->addLoader('yaml', new YamlFileLoader());

	$trans_dir = __DIR__ . '/translation/';

	$translator->addResource('yaml', $trans_dir . 'en.yml', 'en');
	$translator->addResource('yaml', $trans_dir . 'nl.yml', 'nl');

	return $translator;
});

$app->register(new Silex\Provider\FormServiceProvider());

$app->register(new Silex\Provider\CsrfServiceProvider());

$app->register(new Silex\Provider\ValidatorServiceProvider());

$app->register(new Silex\Provider\VarDumperServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), []);

/*
$app->extend('monolog', function($monolog, $app) {

	$monolog->setTimezone(new DateTimeZone('UTC'));

	$handler = new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG);
	$handler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter());
	$monolog->pushHandler($handler);

	$handler = new \Monolog\Handler\RedisHandler($app['predis'], 'monolog_logs', \Monolog\Logger::DEBUG, true, 20);
	$handler->setFormatter(new \Monolog\Formatter\JsonFormatter());
	$monolog->pushHandler($handler);

//	$monolog->pushProcessor(new Monolog\Processor\WebProcessor());

	$monolog->pushProcessor(function ($record) use ($app){

		$record['extra']['schema'] = $app['this_group']->get_schema();

		if (isset($app['s_ary_user']))
		{
			$record['extra']['letscode'] = $app['s_ary_user']['letscode'] ?? '';
			$record['extra']['user_id'] = $app['s_ary_user']['id'] ?? '';
			$record['extra']['username'] = $app['s_ary_user']['name'] ?? '';
		}

		if (isset($app['s_schema']))
		{
			$record['extra']['user_schema'] = $app['s_schema'];
		}

		$record['extra']['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');

		return $record;
	});

	return $monolog;
});
*/

$app->register(new Silex\Provider\SessionServiceProvider(), [
	'session.storage.handler'	=> new service\redis_session($app['predis']),
	'session.storage.options'	=> [
		'name'						=> 'eland',
		'cookie_lifetime'			=> 172800,
	],
]);

$app['schemas'] = function ($app){
	return new service\schemas($app['db']);
};

$app['schema_voter'] = function ($app){
	return new security\schema_voter($app['schemas'], $app['request_stack']);
};

$app->register(new Silex\Provider\SecurityServiceProvider(), [

	'security.firewalls' => [

		'unsecured'	=> [
			'anonymous'	=> true,
		],

		'secured'	=> [
			'pattern'	=>  '^/*/[giuam]/',

			'form' => [
				'login_path' => 'login',
				'check_path' => 'login_check',
			],

			'logout' => [
				'logout_path' 			=> 'logout',
				'invalidate_session' 	=> true,
			],

			'users'		=> function () use ($app) {
				return new security\user_provider($app['db'], $app['xdb']);
			},

		],
	],

/*
	'security.role_hierarchy' => [
		'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'],
	],
*/
]);


$app['security.voters'] = $app->extend('security.voters', function($voters) use ($app) {
    $voters[] = $app['schema_voter'];

    return $voters;
});

if(!isset($rootpath))
{
	$rootpath = './';
}

$app['protocol'] = 'http://';

$app['rootpath'] = $rootpath;

$app['s3_img'] = getenv('S3_IMG') ?: die('Environment variable S3_IMG S3 bucket for images not defined.');
$app['s3_doc'] = getenv('S3_DOC') ?: die('Environment variable S3_DOC S3 bucket for documents not defined.');

$app['s3_protocol'] = 'http://';

$app['s3_img_url'] = $app['s3_protocol'] . $app['s3_img'] . '/';
$app['s3_doc_url'] = $app['s3_protocol'] . $app['s3_doc'] . '/';

$app['s3'] = function($app){
	return new service\s3($app['s3_img'], $app['s3_doc']);
};

/*
 * The locale must be installed in the OS for formatting dates.
 */

setlocale(LC_TIME, 'nl_NL.UTF-8');

date_default_timezone_set((getenv('TIMEZONE')) ?: 'Europe/Brussels');

$app['typeahead'] = function($app){
	return new service\typeahead($app['predis'], $app['monolog']);
};

$app['log_db'] = function($app){
	return new service\log_db($app['db'], $app['predis']);
};

/**
 * Get all eland schemas and domains
 */

$app['groups'] = function ($app){
	return new service\groups($app['db']);
};

$app['template_vars'] = function ($app){
	return new service\template_vars($app['config']);
};

$app['this_group'] = function($app){
	return new service\this_group($app['groups'], $app['db'], $app['predis']);
};

$app['xdb'] = function ($app){
	return new service\xdb($app['db'], $app['predis'], $app['monolog'], $app['this_group']);
};

$app['ev'] = function ($app){
	return new service\ev($app['db'], $app['predis']);
};

$app['cache'] = function ($app){
	return new service\cache($app['db'], $app['predis'], $app['monolog']);
};

$app['boot_count'] = function ($app){
	return new service\boot_count($app['cache']);
};

$app['queue'] = function ($app){
	return new service\queue($app['db'], $app['monolog']);
};

$app['date_format'] = function($app){
	return new service\date_format($app['config']);
};

$app['mailaddr'] = function ($app){
	return new service\mailaddr($app['db'], $app['monolog'], $app['this_group'], $app['config']);
};

$app['interlets_groups'] = function ($app){
	return new service\interlets_groups($app['db'], $app['predis'], $app['groups'],
		$app['config'], $app['protocol']);
};

$app['distance'] = function ($app){
	return new service\distance($app['db'], $app['cache']);
};

$app['config'] = function ($app){
	return new service\config($app['monolog'], $app['db'], $app['xdb'],
		$app['predis'], $app['this_group']);
};

$app['config_en'] = function ($app){
	return new service\config($app['monolog'], $app['db'], $app['xdb'],
		$app['predis']);
};

$app['type_template'] = function ($app){
	return new service\type_template($app['config']);
};

$app['user_cache'] = function ($app){
	return new service\user_cache($app['db'], $app['xdb'], $app['predis'], $app['this_group']);
};

$app['token'] = function ($app){
	return new service\token();
};

$app['unique_eid'] = function ($app){
	return new service\unique_eid($app['xdb'], $app['token']);
};

$app['email_validate'] = function ($app){
	return new service\email_validate($app['cache'], $app['xdb'], $app['token'], $app['monolog']);
};

$app['mail'] = function ($app){
	return new service\mail($app['queue'], $app['monolog'],
		$app['mailaddr'], $app['twig'], $app['config'],
		$app['email_validate']);
};

// tasks

$app['task.cleanup_cache'] = function ($app){
	return new task\cleanup_cache($app['cache'], $app['schedule']);
};

$app['task.cleanup_image_files'] = function ($app){
	return new task\cleanup_image_files($app['cache'], $app['db'], $app['monolog'],
		$app['s3'], $app['groups'], $app['schedule']);
};

$app['task.cleanup_logs'] = function ($app){
	return new task\cleanup_logs($app['db'], $app['schedule']);
};

$app['task.get_elas_interlets_domains'] = function ($app){
	return new task\get_elas_interlets_domains($app['db'], $app['cache'],
		$app['schedule'], $app['groups']);
};

$app['task.fetch_elas_interlets'] = function ($app){
	return new task\fetch_elas_interlets($app['cache'], $app['predis'], $app['typeahead'],
		$app['monolog'], $app['schedule']);
};

// schema tasks (tasks applied to every group seperate)

$app['schema_task.sync_user_cache'] = function ($app){
	return new schema_task\sync_user_cache($app['db'], $app['user_cache'],
		$app['schedule'], $app['groups'], $app['this_group']);
};

$app['schema_task.cleanup_messages'] = function ($app){
	return new schema_task\cleanup_messages($app['db'], $app['monolog'],
		$app['schedule'], $app['groups'], $app['this_group'], $app['config']);
};

$app['schema_task.cleanup_news'] = function ($app){
	return new schema_task\cleanup_news($app['db'], $app['xdb'], $app['monolog'],
		$app['schedule'], $app['groups'], $app['this_group']);
};

$app['schema_task.geocode'] = function ($app){
	return new schema_task\geocode($app['db'], $app['cache'],
		$app['monolog'], $app['queue.geocode'],
		$app['schedule'], $app['groups'], $app['this_group']);
};

$app['schema_task.saldo_update'] = function ($app){
	return new schema_task\saldo_update($app['db'], $app['monolog'],
		$app['schedule'], $app['groups'], $app['this_group']);
};

$app['schema_task.user_exp_msgs'] = function ($app){
	return new schema_task\user_exp_msgs($app['db'], $app['mail'],
		$app['protocol'],
		$app['schedule'], $app['groups'], $app['this_group'],
		$app['config'], $app['template_vars'], $app['user_cache']);
};

$app['schema_task.saldo'] = function ($app){
	return new schema_task\saldo($app['db'], $app['xdb'], $app['predis'], $app['cache'],
		$app['monolog'], $app['mail'],
		$app['s3_img_url'], $app['s3_doc_url'], $app['protocol'],
		$app['date_format'], $app['distance'],
		$app['schedule'], $app['groups'], $app['this_group'],
		$app['interlets_groups'], $app['config']);
};

$app['schema_task.interlets_fetch'] = function ($app){
	return new schema_task\interlets_fetch($app['predis'], $app['db'], $app['xdb'], $app['cache'],
		$app['typeahead'], $app['monolog'],
		$app['schedule'], $app['groups'], $app['this_group']);
};

//

$app['schedule'] = function ($app){
	return new service\schedule($app['cache'], $app['predis']);
};

// queue

$app['queue.geocode'] = function ($app){
	return new queue\geocode($app['db'], $app['cache'], $app['queue'], $app['monolog'], $app['user_cache']);
};

/**
 * functions
 */

function link_user($user, string $sch = '', $link = true, $show_id = false, $field = '')
{
	global $rootpath, $app;

	if (!$user)
	{
		return '<i>** leeg **</i>';
	}

	$user = is_array($user) ? $user : $app['user_cache']->get($user, $sch);
	$str = ($field) ? $user[$field] : $user['letscode'] . ' ' . $user['name'];
	$str = ($str == '' || $str == ' ') ? '<i>** leeg **</i>' : htmlspecialchars($str, ENT_QUOTES);

	if ($link)
	{
		$param = ['id' => $user['id']];
		if (is_string($link))
		{
			$param['link'] = $link;
		}
		$out = '<a href="';
		$out .= generate_url('users', $param, $sch);
		$out .= '">' . $str . '</a>';
	}
	else
	{
		$out = $str;
	}

	$out .= ($show_id) ? ' (id: ' . $user['id'] . ')' : '';

	return $out;
}

/**
 *
 */

$app->register(new Silex\Provider\HttpFragmentServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app->register(new Silex\Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/cache/profiler',
    'profiler.mount_prefix' => '/_profiler',
));

$app['uuid'] = function($app){
	return new service\uuid();
};

$app->error(function (\Exception $e, Symfony\Component\HttpFoundation\Request $request, $code) use ($app) {
    if ($app['debug'])
    {
        return;
    }

    // ... logic to handle the error and return a Response

	switch ($code)
	{
		case 404:
			$message = '404. The requested page could not be found.';
			break;
		default:
			$message =  $code . '. We are sorry, but something went wrong.';
	}

    return new Response($message);
});

return $app;
