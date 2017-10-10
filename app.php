<?php

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/vendor/autoload.php';

$app = new util\app();

$app['route_class'] = 'util\route';

$app['protocol'] = 'https://';

$app['debug'] = getenv('DEBUG') ? true : false;

if (php_sapi_name() !== 'cli')
{
	\Symfony\Component\Debug\ErrorHandler::register();
	\Symfony\Component\Debug\ExceptionHandler::register($app['debug']);
}

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
//		'bootstrap_3_layout.html.twig',
		'form.html.twig',
	],
]);


$app->extend('twig', function($twig, $app) {

	$policy = new Twig_Sandbox_SecurityPolicy();
	$twig->addExtension(new Twig_Extension_Sandbox($policy));
	$twig->addExtension(new twig\extension());
	$twig->addRuntimeLoader(new \Twig_FactoryRuntimeLoader([
		twig\config::class => function() use ($app){
			return new twig\config($app['config']);
		},
		twig\distance::class => function() use ($app){
			return new twig\distance($app['db'], $app['cache']);
		},
		twig\date_format::class => function() use ($app){
			return new twig\date_format($app['config'], $app['translator'], $app['schema']);
		},
		twig\mail_date::class => function() use ($app){
			return new twig\mail_date($app['date_format_cache']);
		},
		twig\web_date::class => function() use ($app){
			return new twig\web_date($app['date_format_cache'], $app['request_stack']);
		},
		twig\web_user::class => function () use ($app){
			return new twig\web_user($app['user_simple_cache'], $app['request_stack'],
				$app['url_generator']);
		},
/*
		twig\pagination::class => function() use ($app){
			return new twig\pagination();
		},
*/
	]));
	$twig->addGlobal('s3_img', getenv('S3_IMG'));
	$twig->addGlobal('s3_doc', getenv('S3_DOC'));

	return $twig;
});


$app->register(new Silex\Provider\AssetServiceProvider(), [
    'assets.version' => '15',
    'assets.version_format' => '%s?v=%s',
    'assets.base_path' => '/js',
    'assets.named_packages' => [
        'css' 		=> ['base_path' => '/css', 'version' => '15', 'version_format' => '%s?v=%s'],
        'js'		=> ['base_path'	=> '/js', 'version' => '15', 'version_format' => '%s?v=%s'],
        'loc_img'	=> ['base_path'	=> '/img', 'version' => '15', 'version_format' => '%s?v=%s'],
        'img' 		=> ['base_urls' => [getenv('S3_IMG')]],
		'doc'		=> ['base_urls' => [getenv('S3_DOC')]],
		's3'		=> ['base_urls' => [getenv('S3')]],
        'maxcdn'	=> ['base_urls' => ['https://maxcdn.bootstrapcdn.com']],
        'cdnjs'		=> ['base_urls'	=> ['https://cdnjs.cloudflare.com/ajax/libs']],
        'jquery'	=> ['base_urls'	=> ['https://code.jquery.com']],
    ],
]);

/*
 * The locale must be installed in the OS for formatting dates.
 */

setlocale(LC_TIME, 'nl_NL.UTF-8');
date_default_timezone_set((getenv('TIMEZONE')) ?: 'Europe/Brussels');

$app->register(new Silex\Provider\LocaleServiceProvider());

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => ['nl', 'en'],
    'locale'			=> 'nl',
));

$app->extend('translator', function($translator, $app) {
	$loader = new Symfony\Component\Translation\Loader\YamlFileLoader();
	$translator->addLoader('yaml', $loader);
	$trans_dir = __DIR__ . '/translation/';
	$translator->addResource('yaml', $trans_dir . 'en.yml', 'en');
	$translator->addResource('yaml', $trans_dir . 'nl.yml', 'nl');
	return $translator;
});

$app['etoken_manager'] = function ($app){
	return new form\etoken_manager($app['predis']);
};

$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\CsrfServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\VarDumperServiceProvider());
$app->register(new Silex\Provider\MonologServiceProvider(), []);

$app->extend('form.type.extensions', function($extensions) use ($app) {

    $extensions[] = new form\form_type_etoken_extension(
		$app['etoken_manager'], $app['translator']
	);

	$extensions[] = new form\form_type_extra_var_extension();

    return $extensions;
});

$app->extend('form.types', function ($types) use ($app) {
	$types[] = 'datepicker_type';
	$types[] = 'category_type';
	$types[] = 'type_contact_type';
	$types[] = 'typeahead_type';
	$types[] = 'typeahead_user_type';
	$types[] = 'transaction_filter_type';

    return $types;
});

$app['datepicker_transformer'] = function ($app){
	return new transformer\datepicker_transformer($app['schema']);
};

$app['datepicker_type'] = function ($app) {
    return new form\datepicker_type($app['datepicker_transformer']);
};

$app['category_type'] = function ($app) {
	return new form\category_type($app['db'], 
		$app['translator'], $app['schema']);
};

$app['type_contact_type'] = function ($app) {
	return new form\type_contact_type($app['db'], $app['schema']);
};

$app['typeahead_type_attr'] = function ($app) {
	return new form\typeahead_type_attr($app['thumbprint'],
		$app['request_stack'], $app['url_generator']);
};

$app['typeahead_type'] = function ($app) {
	return new form\typeahead_type($app['typeahead_type_attr']);
};

$app['typeahead_user_transformer'] = function ($app) {
	return new transformer\typeahead_user_transformer($app['db'], $app['schema']);
};

$app['typeahead_user_type'] = function ($app) {
	return new form\typeahead_user_type($app['typeahead_user_transformer']);
};

$app['transaction_filter_type'] = function ($app) {
	return new form\transaction_filter_type();
};

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

$app['thumbprint'] = function ($app){
	$version = getenv('THUMBPRINT_VERSION') ?: '';
	return new service\thumbprint($app['predis'], $version);
};

$app['schema'] = function ($app){
	return $app['request_stack']->getCurrentRequest()->attributes->get('schema');
};

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

$app['s3_img'] = getenv('S3_IMG') ?: die('Environment variable S3_IMG S3 bucket for images not defined.');
$app['s3_doc'] = getenv('S3_DOC') ?: die('Environment variable S3_DOC S3 bucket for documents not defined.');

$app['s3_protocol'] = 'http://';

$app['s3_img_url'] = $app['s3_protocol'] . $app['s3_img'] . '/';
$app['s3_doc_url'] = $app['s3_protocol'] . $app['s3_doc'] . '/';

$app['s3'] = function($app){
	return new service\s3($app['s3_img'], $app['s3_doc']);
};

/**/

$app['date_format_cache'] = function ($app){
	return new service\date_format_cache($app['predis'], $app['config'], $app['translator']);
};

// 

$app['pagination'] = function (){
	return new service\pagination();
};

$app['log_db'] = function($app){
	return new service\log_db($app['db'], $app['predis']);
};

$app['groups'] = function ($app){
	return new service\groups($app['db']);
};

$app['this_group'] = function($app){
	return new service\this_group($app['groups'], $app['db'], $app['predis']);
};

$app['xdb'] = function ($app){
	return new service\xdb($app['db']);
};

$app['ev'] = function ($app){
	return new service\ev($app['db'], $app['predis'], $app['uuid']);
};

$app['c_ev'] = function ($app){
	return new service\c_ev($app['ev']);
};

$app['u_ev'] = function ($app){
	return new service\u_ev($app['u_ev']);
};

$app['migrate_elas'] =function ($app){
	return new service\migrate_elas($app['db'], $app['xdb'], $app['cache'], $app['ev']);
};

$app['sync_elas'] =function ($app){
	return new service\sync_elas($app['db'], $app['xdb'], $app['cache'], $app['ev']);
};

$app['cache'] = function ($app){
	return new service\cache($app['db'], $app['predis']);
};

$app['boot_count'] = function ($app){
	return new service\boot_count($app['cache']);
};

$app['queue'] = function ($app){
	return new service\queue($app['db']);
};

$app['date_format'] = function($app){
	return new service\date_format($app['config']);
};

$app['mailaddr'] = function ($app){
	return new service\mailaddr($app['db'], $app['monolog'], 
	$app['this_group'], $app['config']);
};

$app['interlets_groups'] = function ($app){
	return new service\interlets_groups($app['db'], 
		$app['predis'], $app['groups'],
		$app['config'], $app['protocol']);
};

$app['distance'] = function ($app){
	return new service\distance($app['db'], $app['cache']);
};

$app['config'] = function ($app){
	return new service\config($app['db'], $app['xdb'],
		$app['predis']);
};

$app['type_template'] = function ($app){
	return new service\type_template($app['config']);
};

$app['user_cache'] = function ($app){
	return new service\user_cache($app['db'], $app['xdb'], $app['predis']);
};

$app['user_simple_cache'] = function ($app){
	return new service\user_simple_cache($app['db'], $app['predis']);
};

$app['token'] = function ($app){
	return new service\token();
};

$app['email_validate'] = function ($app){
	return new service\email_validate($app['cache'], $app['xdb'], $app['token'], $app['monolog']);
};

$app['mail'] = function ($app){
	return new service\mail($app['queue'], $app['monolog'],
		$app['mailaddr'], $app['twig'], $app['config'],
		$app['email_validate']);
};

// elas

$app['elas_ev'] = function($app){
	return new elas\elas_ev($app['ev']);
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
	return new task\fetch_elas_interlets($app['cache'], $app['predis'], $app['thumbprint'],
		$app['monolog'], $app['schedule']);
};

// schema tasks (tasks applied to every group seperate)

$app['schema_task.sync_user_cache'] = function ($app){
	return new schema_task\sync_user_cache($app['db'], $app['user_cache'],
		$app['schedule'], $app['groups'], $app['this_group']);
};

$app['schema_task.cleanup_messages'] = function ($app){
	return new schema_task\cleanup_messages($app['db'], $app['monolog'],
		$app['schedule'], $app['groups'], $app['this_group'], $app['config'],
		$app['url_generator']);
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
		$app['thumbprint'], $app['monolog'],
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

//

$app->register(new Knp\Provider\ConsoleServiceProvider());

$app->register(new Silex\Provider\HttpFragmentServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app->register(new Silex\Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/cache/profiler',
    'profiler.mount_prefix' => '/_profiler',
));

$app['uuid'] = function($app){
	return new service\uuid();
};

$app->error(function (\Exception $e, Request $request, $code) use ($app) {

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

// repositories

$app['user_repository'] = function ($app){
	return new repository\user_repository($app['db'], $app['xdb'], $app['predis']);
};

$app['ad_repository'] = function ($app){
	return new repository\ad_repository($app['db']);
};

$app['type_contact_repository'] = function ($app){
	return new repository\type_contact_repository($app['db']);
};

$app['contact_repository'] = function ($app){
	return new repository\contact_repository($app['db']);
};

$app['category_repository'] = function ($app){
	return new repository\category_repository($app['db']);
};

$app['news_repository'] = function ($app){
	return new repository\news_repository($app['db'], $app['xdb']);
};

$app['doc_repository'] = function ($app){
	return new repository\doc_repository($app['xdb']);
};

$app['forum_repository'] = function ($app){
	return new repository\forum_repository($app['xdb']);
};

$app['page_repository'] = function ($app){
	return new repository\page_repository($app['xdb']);
};

$app['transaction_repository'] = function ($app){
	return new repository\transaction_repository($app['db']);
};

// converters 

$app['category_converter'] = function ($app){
	return new converter\category_converter($app['category_repository']);
};

$app['ad_converter'] = function ($app){
	return new converter\ad_converter($app['ad_repository']);
};

$app['type_contact_converter'] = function ($app){
	return new converter\type_contact_converter($app['type_contact_repository']);
};

$app['contact_converter'] = function ($app){
	return new converter\contact_converter($app['contact_repository']);
};

$app['user_converter'] = function ($app){
	return new converter\user_converter($app['user_repository']);
};

$app['news_converter'] = function ($app){
	return new converter\news_converter($app['news_repository']);
};

$app['transaction_converter'] = function ($app){
	return new converter\transaction_converter($app['transaction_repository']);
};

$app['doc_converter'] = function ($app){
	return new converter\doc_converter($app['doc_repository']);
};

$app['forum_converter'] = function ($app){
	return new converter\forum_converter($app['forum_repository']);
};

$app['page_converter'] = function ($app){
	return new converter\page_converter($app['page_repository']);
};
 
require __DIR__ . '/routing.php';

return $app;
