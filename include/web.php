<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/default.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->register(new Silex\Provider\SessionServiceProvider(), [
	'session.storage.handler'	=> function ($app) {
		return new Predis\Session\Handler(
			$app['predis'], ['gc_maxlifetime' => 172800]
		);
	},
	'session.storage.options'	=> [
		'name'						=> 'eland',
		'cookie_domain'				=> '.' . getenv('OVERALL_DOMAIN'),
		'cookie_lifetime'			=> 172800,
	],
]);

$app['page_access'] = $page_access;

$header_allow_origin = $app['s3_protocol'] . $app['s3_img'] . ', ';
$header_allow_origin .= $app['s3_protocol'] . $app['s3_doc'];

header('Cache-Control: private, no-cache');
header('Access-Control-Allow-Origin: ' . $header_allow_origin);

$app['assets'] = function($app){
	return new service\assets($app['rootpath']);
};

$app['assets']->add(['jquery', 'bootstrap', 'fontawesome', 'footable', 'autocomplete', 'base.css', 'print.css', 'base.js']);

$app['script_name'] = str_replace('.php', '', ltrim($_SERVER['SCRIPT_NAME'], '/'));

$app['base_url'] = $app['protocol'] . $_SERVER['SERVER_NAME'];

$post = $_SERVER['REQUEST_METHOD'] == 'GET' ? false : true;

$app['mapbox_token'] = getenv('MAPBOX_TOKEN');

if (isset($_GET['ev']))
{
	$app['email_validate']->validate($_GET['ev']);
}

/**
 * vars
 **/

$top_right = '';
$top_buttons = '';

$role_ary = [
	'admin'		=> 'Admin',
	'user'		=> 'User',
	//'guest'		=> 'Guest', //is not a primary role, but a speudo role
	'interlets'	=> 'InterSysteem',
];

$status_ary = [
	0	=> 'Gedesactiveerd',
	1	=> 'Actief',
	2	=> 'Uitstapper',
	//3	=> 'Instapper',    // not used in selector
	//4 => 'Secretariaat, // not used
	5	=> 'Info-pakket',
	6	=> 'Info-moment',
	7	=> 'Extern',
];

$access_ary = [
	'admin'		=> 0,
	'user'		=> 1,
	'guest'		=> 2,
	'anonymous'	=> 3,
];

$allowed_interlets_landing_pages = [
//	'index'			=> true,
	'messages'		=> true,
	'users'			=> true,
	'transactions'	=> true,
	'news'			=> true,
	'docs'			=> true,
];

/*
 * check if we are on the request hosting url.
 */
$key_host_env = str_replace('.', '__', strtoupper($_SERVER['SERVER_NAME']));

if ($app['script_name'] == 'index' && getenv('HOSTING_FORM_' . $key_host_env))
{
	$page_access = 'anonymous';
	$hosting_form = true;
	return;
}

/*
 * permanent redirects
 */

if ($redirect = getenv('REDIRECT_' . $key_host_env))
{
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $app['protocol'] . $redirect . $_SERVER['REQUEST_URI']);
	exit;
}

/* */

$app['alert'] = function ($app){
	return new service\alert($app['monolog'], $app['session']);
};

$app['pagination'] = function (){
	return new service\pagination();
};

$app['password_strength'] = function ($app){
	return new service\password_strength();
};

$app['user'] = function ($app){
	return new service\user($app['this_group'], $app['monolog'], $app['session'], $app['page_access']);
};

$app['autominlimit'] = function ($app){
	return new service\autominlimit($app['monolog'], $app['xdb'], $app['db'],
		$app['this_group'], $app['config'], $app['user_cache']);
};

// init

$app['elas_db_upgrade'] = function ($app){
	return new service\elas_db_upgrade($app['db']);
};

/** **/

if (!$app['this_group']->get_schema())
{
	http_response_code(404);

	echo $app['twig']->render('404.html.twig');
	exit;
}

/** user **/

$p_role = $_GET['r'] ?? 'anonymous';
$p_user = $_GET['u'] ?? false;
$p_schema = $_GET['s'] ?? false;

$s_schema = $p_schema ?: $app['this_group']->get_schema();
$s_id = $p_user;
$s_accountrole = isset($access_ary[$p_role]) ? $p_role : 'anonymous';

$s_group_self = $s_schema == $app['this_group']->get_schema() ? true : false;

/** access user **/

$logins = $app['session']->get('logins') ?? [];

$s_master = $s_elas_guest = false;

if (!count($logins))
{
	if ($s_accountrole != 'anonymous')
	{
		redirect_login();
	}
}

if (!$s_id)
{
	if ($page_access != 'anonymous')
	{
		if (isset($logins[$s_schema]) && ctype_digit((string) $logins[$s_schema]))
		{
			$s_id = $logins[$s_schema];

			$location = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
			$get = $_GET;

			unset($get['u'], $get['s'], $get['r']);

			$session_user = $app['user_cache']->get($s_id, $s_schema);

			$get['r'] = $session_user['accountrole'];
			$get['u'] = $s_id;

			if (!$s_group_self)
			{
				$get['s'] = $s_schema;
			}

			$get = http_build_query($get);
			header('Location: ' . $location . '?' . $get);
			exit;
		}

		redirect_login();
	}

	if ($s_accountrole != 'anonymous')
	{
		redirect_login();
	}
}
else if (!isset($logins[$s_schema]))
{
	if ($s_accountrole != 'anonymous')
	{
		redirect_login();
	}
}
else if ($logins[$s_schema] != $s_id || !$s_id)
{
	$s_id = $logins[$s_schema];

	if (ctype_digit((string) $s_id))
	{
		$location = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$get = $_GET;

		unset($get['u'], $get['s'], $get['r']);

		$session_user = $app['user_cache']->get($s_id, $s_schema);

		$get['r'] = $session_user['accountrole'];
		$get['u'] = $s_id;

		if (!$s_group_self)
		{
			$get['s'] = $s_schema;
		}

		$get = http_build_query($get);
		header('Location: ' . $location . '?' . $get);
		exit;
	}

	redirect_login();
}
else if (ctype_digit((string) $s_id))
{
	$session_user = $app['user_cache']->get($s_id, $s_schema);

	if (!$s_group_self && $s_accountrole != 'guest')
	{
		$location = $app['protocol'] . $app['groups']->get_host($s_schema) . '/messages.php?r=';
		$location .= $session_user['accountrole'] . '&u=' . $s_id;
		header('Location: ' . $location);
		exit;
	}

	if ($access_ary[$session_user['accountrole']] > $access_ary[$s_accountrole])
	{
		$s_accountrole = $session_user['accountrole'];

		redirect_default_page();
	}

	if (!($session_user['status'] == 1 || $session_user['status'] == 2))
	{
		$app['session']->invalidate();
		redirect_login();
	}
}
else if ($s_id == 'elas')
{
	if ($s_accountrole != 'guest' || !$s_group_self)
	{
		redirect_login();
	}

	$s_elas_guest = true;
}
else if ($s_id == 'master')
{
	if (!$s_group_self && $s_accountrole != 'guest')
	{
		$location = $app['protocol'] . $app['groups']->get_host($s_schema) . '/messages.php?r=admin&u=master';
		header('Location: ' . $location);
		exit;
	}

	$s_master = true;
}
else
{
	redirect_login();
}

/** page access **/

if (!isset($page_access))
{
	http_response_code(500);

	echo $app['twig']->render('500.html.twig');
	exit;
}

if (getenv('WEBSITE_MAINTENANCE'))
{
	echo $app['twig']->render('website_maintenance.html.twig', ['message' =>  getenv('WEBSITE_MAINTENANCE')]);
	exit;
}

switch ($s_accountrole)
{
	case 'anonymous':

		if ($page_access != 'anonymous')
		{
			redirect_login();
		}

		break;

	case 'guest':

		if ($page_access != 'guest')
		{
			redirect_default_page();
		}

		break;

	case 'user':

		if (!($page_access == 'user' || $page_access == 'guest'))
		{
			redirect_default_page();
		}

		break;

	case 'admin':

		if ($page_access == 'anonymous')
		{
			redirect_default_page();
		}

		break;

	default:

		redirect_login();

		break;
}


/**
 * some vars
 **/

$access_level = $access_ary[$s_accountrole];

$app['s_admin'] = $s_admin = $s_accountrole === 'admin';
$s_user = $s_accountrole === 'user';
$s_guest = $s_accountrole === 'guest';
$s_anonymous = !($s_admin || $s_user || $s_guest);

$errors = [];

/**
 * check access to groups
 **/

if ($app['config']->get('template_lets', $app['this_group']->get_schema())
	&& $app['config']->get('interlets_en', $app['this_group']->get_schema()))
{
	$elas_interlets_groups = $app['interlets_groups']->get_elas($s_schema);
	$eland_interlets_groups = $app['interlets_groups']->get_eland($s_schema);
}
else
{
	$elas_interlets_groups = $eland_interlets_groups = [];
}

if ($s_group_self && $s_guest)
{
	$elas_interlets_groups = $eland_interlets_groups = [];
}

$count_interlets_groups = count($eland_interlets_groups) + count($elas_interlets_groups);
$app['count_interlets_groups'] = $count_interlets_groups;

if ($page_access != 'anonymous'
	&& !$s_group_self
	&& !$eland_interlets_groups[$app['this_group']->get_schema()])
{
	header('Location: ' . generate_url('messages', ['view' => $view_messages], $s_schema));
	exit;
}

if ($page_access != 'anonymous' && !$s_admin
	&& $app['config']->get('maintenance', $app['this_group']->get_schema()))
{
	echo $app['twig']->render('maintenance.html.twig');
	exit;
}

 /**
  *
  */

$app['xdb']->set_user($s_schema, ctype_digit((string) $s_id) ? $s_id : 0);

$app['form_token'] = function ($app){
	return new service\form_token($app['predis'], $app['script_name']);
};

/* view (global for all groups) */

$inline = isset($_GET['inline']) ? true : false;

$view = $_GET['view'] ?? false;

$view_users = $app['session']->get('view.users') ?? 'list';
$view_messages = $app['session']->get('view.messages') ?? 'extended';
$view_news = $app['session']->get('view.news') ?? 'extended';

if ($view || $inline)
{
	if ($app['script_name'] == 'users' && $view != $view_users)
	{
		$view = $view_users = $view ?: $view_users;
		$app['session']->set('view.users', $view_users);
	}
	else if ($app['script_name'] == 'messages' && $view != $view_messages)
	{
		$view = $view_messages = $view ?: $view_messages;
		$app['session']->set('view.messages', $view);
	}
	else if ($app['script_name'] == 'news' && $view != $view_news)
	{
		$view = $view_news = $view ?: $view_news;
		$app['session']->set('view.news', $view);
	}
}

/**
 * remember adapted role in own group (for links to own group)
 */

if (!$s_anonymous)
{
	if ($s_master || $session_user['accountrole'] == 'admin' || $session_user['accountrole'] == 'user')
	{
		if (isset($logins[$app['this_group']->get_schema()]) && $s_group_self)
		{
			$app['session']->set('role.' . $app['this_group']->get_schema(), $s_accountrole);
		}

		$s_user_params_own_group = [
			'r' => $app['session']->get('role.' . $s_schema),
			'u'	=> $s_id,
		];
	}
	else
	{
		$s_user_params_own_group = [];
	}
}

/* some more vars */

$app['s_ary_user'] = $session_user ?? [];
$app['s_schema'] = $s_schema;

$newusertreshold = time() - $app['config']->get('newuserdays', $app['this_group']->get_schema()) * 86400;

/** welcome message **/

if (isset($_GET['welcome']) && $s_guest)
{
	$msg = '<strong>Welkom bij ';
	$msg .= $app['config']->get('systemname', $app['this_group']->get_schema());
	$msg .= '</strong><br>';
	$msg .= 'Waardering bij ';
	$msg .= $app['config']->get('systemname', $app['this_group']->get_schema());
	$msg .= ' gebeurt met \'';
	$msg .= $app['config']->get('currency', $app['this_group']->get_schema());
	$msg .= '\'. ';
	$msg .= $app['config']->get('currencyratio', $app['this_group']->get_schema());
	$msg .= ' ';
	$msg .= $app['config']->get('currency', $app['this_group']->get_schema());
	$msg .= ' stemt overeen met 1 uur.<br>';

	if ($s_elas_guest)
	{
		$msg .= 'Je bent ingelogd als gast, je kan informatie ';
		$msg .= 'raadplegen maar niets wijzigen. Transacties moet je ';
		$msg .= 'ingeven in je eigen Systeem.';
	}
	else
	{
		$msg .= 'Je kan steeds terug naar je eigen Systeem via het menu <strong>Systeem</strong> ';
		$msg .= 'boven in de navigatiebalk.';
	}

	$app['alert']->info($msg);
}

$app['access_control'] = function($app){
	return new service\access_control($app['this_group'], $app['config']);
};

/**************** FUNCTIONS ***************/

function btn_item_nav(string $url, bool $next, bool $down):string
{
	$ret = ' class="btn btn-default" title="';
	$ret .= $next ? 'Volgende' : 'Vorige';
	$ret .= '"><i class="fa fa-chevron-';
	$ret .= $down ? 'down' : 'up';
	$ret .= '"></i>';

	if ($url)
	{
		return '<a href="' . $url . '"' . $ret . '</a>';
	}

	return '<button disabled="disabled"' . $ret . '</button>';
}

function btn_filter():string
{
	$ret = '<div class="pull-right">';
	$ret .= '&nbsp;<button class="btn btn-default hidden-xs" title="Filters" ';
	$ret .= 'data-toggle="collapse" data-target="#filter"';
	$ret .= '><i class="fa fa-caret-down"></i><span class="hidden-xs hidden-sm"> Filters</span></button>';
	$ret .= '</div>';
	return $ret;
}

/*
 * create link within eland with query parameters depending on user and role
 */

function aphp(
	string $entity = '',
	array $params = [],
	string $label = '*link*',
	$class = false,
	$title = false,
	$fa = false,
	$collapse = false,
	$attr = false,
	$sch = false):string
{
	$out = '<a href="' .  generate_url($entity, $params, $sch) . '"';
	$out .= $class ? ' class="' . $class . '"' : '';
	$out .= $title ? ' title="' . $title . '"' : '';
	if (is_array($attr))
	{
		foreach ($attr as $name => $val)
		{
			$out .= ' ' . $name . '="' . $val . '"';
		}
	}
	$out .= '>';
	$out .= $fa ? '<i class="fa fa-' . $fa .'"></i>' : '';
	$out .= $collapse ? '<span class="hidden-xs hidden-sm"> ' : ' ';
	$out .= htmlspecialchars($label, ENT_QUOTES);
	$out .= $collapse ? '</span>' : '';
	$out .= '</a>';
	return $out;
}

/**
 * generate url
 */
function generate_url(string $entity, $params = [], $sch = false):string
{
	global $rootpath, $app;

	if ($app['alert']->is_set())
	{
		$params['a'] = '1';
	}

	$params = array_merge($params, get_session_query_param($sch));

	$params = http_build_query($params);

	$params = $params ? '?' . $params : '';

	$path = $sch ? $app['protocol'] . $app['groups']->get_host($sch) . '/' : $rootpath;

	return $path . $entity . '.php' . $params;
}

/**
 * get session query param
 */
function get_session_query_param($sch = false):array
{
	global $p_role, $p_user, $p_schema, $access_level;
	global $s_user_params_own_group, $s_id, $s_schema;
	static $ary;

	if ($sch)
	{
		if ($sch == $s_schema)
		{
			return  $s_user_params_own_group;
		}

		if ($s_schema)
		{
			$param_ary = ['r' => 'guest', 'u' => $s_id, 's' => $s_schema];

			return $param_ary;
		}

		return ['r' => 'guest'];
	}

	if (isset($ary))
	{
		return $ary;
	}

	$ary = [];

	if ($p_role != 'anonymous')
	{
		$ary['r'] = $p_role;
		$ary['u'] = $p_user;

		if ($access_level == 2 && $p_schema)
		{
			$ary['s'] = $p_schema;
		}
	}

	return $ary;
}

function redirect_default_page()
{
	global $p_role, $p_user, $p_schema, $access_level, $access_session;
	global $s_id, $s_accountrole, $s_schema;

	$access_level = $access_session;

	$p_schema = $s_schema;
	$p_user = $s_id;
	$p_role = $s_accountrole;

	header('Location: ' . get_default_page());
	exit;
}

function get_default_page():string
{
	global $view_messages, $view_users, $view_news, $app;
	static $default_page;

	if (isset($default_page))
	{
		return $default_page;
	}

	$page = $app['config']->get('default_landing_page', $app['this_group']->get_schema());

	$param = [];

	switch ($page)
	{
		case 'messages':
		case 'users':
		case 'news':

			$view_param = 'view_' . $page;
			$param['view'] = $$view_param;

			break;

		default:

			break;
	}

	$default_page = generate_url($page, $param);

	return $default_page;
}

/**
 *
 */
function redirect_login()
{
	global $rootpath;
	$location = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$get = $_GET;
	unset($get['u'], $get['s'], $get['r']);
	$query_string = http_build_query($get);
	$location .= ($query_string == '') ? '' : '?' . $query_string;
	header('Location: ' . $rootpath . 'login.php?location=' . urlencode($location));
	exit;
}

function get_select_options(array $option_ary, $selected):string
{
	$str = '';

	foreach ($option_ary as $key => $value)
	{
		$str .= '<option value="' . $key . '"';
		$str .= $key == $selected ? ' selected="selected"' : '';
		$str .= '>' . htmlspecialchars($value, ENT_QUOTES) . '</option>';
	}

	return $str;
}

function array_intersect_key_recursive(array $ary_1, array $ary_2)
{
	$ary_1 = array_intersect_key($ary_1, $ary_2);

    foreach ($ary_1 as $key => &$val)
    {
        if (is_array($val))
        {
            $val = is_array($ary_2[$key]) ? array_intersect_key_recursive($val, $ary_2[$key]) : $val;
        }
	}

    return $ary_1;
}

/** (dev) */
function print_r2($val)
{
	echo '<pre>';
	print_r($val);
	echo  '</pre>';
}