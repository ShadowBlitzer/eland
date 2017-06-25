<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use util\app;

// development server

$filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);

if (php_sapi_name() === 'cli-server' && is_file($filename))
{
    return false;
}

$app = require_once __DIR__ . '/../app.php';

$app['controllers']->assert('id', '\d+')
	->assert('schema', '[a-z][a-z0-9]+')
	->assert('token', '[a-z0-9][a-z0-9-]+[a-z0-9]');

$cc = $app['controllers_factory'];

$cc->match('/login', 'controller\\auth::login')
	->bind('login');
$cc->match('/password-reset', 'controller\\auth::password_reset')
	->bind('password_reset');
$cc->match('/password-reset/{token}', 'controller\\auth::password_reset_confirm')
	->bind('password_reset');
$cc->get('/logout', 'controller\\auth::logout')
	->bind('logout');
$cc->match('/register', 'controller\\auth::register')
	->bind('register');
$cc->match('/register/{token}', 'controller\\auth::register_confirm')
	->bind('register_confirm');
$cc->match('/contact', 'controller\\contact::contact')
	->bind('contact');
$cc->get('/contact/{token}', 'controller\\contact::contact_confirm')
	->bind('contact_confirm');

$cc->get('/', function (Request $request, app $app, $schema){

	return ' ok test ' . $schema;
});

$cc->before(function(Request $request) use ($app){

	$schema = $request->attributes->get('_route_params')['schema'];

	if (!isset($schema) || !$app['schemas']->is_set($schema))
	{
		$app->abort(404, 'error.404_page_not_found');
	}

	$app['db']->exec('set search_path to ' . $schema);
});



$app->mount('/{schema}', $cc);

$users = $app['controllers_factory'];

$users->assert('user_type', '^(\'active|new|leaving|intertrade|pre-active|post-active|all\')$')
	->assert('user', '\d+')
	->convert('user', 'service\\user_cache::get');

$users->match('/add', 'controller\\user::add')->bind('user_add');   // admin
$users->match('/{user}/edit', 'controller\\user::edit')->bind('user_edit'); // +admin
$users->match('/{user}/del', 'controller\\user::del')->bind('user_del');   // admin

$users->get('/{user_type}/{user}', 'controller\\user::show_with_type')->bind('user_show_with_type');
$users->get('/{user}', 'controller\\user::show')->bind('user_show');
$users->get('/', 'controller\\user::index');



$register = $app['controllers_factory'];
$register->match('/', 'controller\\register::index')->bind('register');
$register->get('/{token}', 'controller\\register::token')
	->bind('register_token');




$public = $app['controllers_factory'];

$public->get('/', function(){
	return 'public';
});

$app->mount('/', $public);



$app->match('/hosting-request', 'controller\\main::hosting_request')->bind('hosting_request');
$app->get('/monitor', 'controller\\main::monitor')->bind('monitor');
$app->get('/', 'controller\\main::index')->bind('main_index');


/*
$app->match('/contact', 'controller\\contact::contact')->bind('contact');
$app->get('/contact-confirm/{token}', 'controller\\contact::contact_confirm')->bind('contact_confirm');

$app->match('/newsletter', 'controller\\contact::newsletter_subscribe')
	->bind('newsletter_subscribe');
$app->get('/newsletter/{token}', 'controller\\contact::newsletter_subscribe_confirm')
	->bind('newsletter_subscribe_confirm');

$app->get('/', function (Request $request, app $app)
{
    return $app['twig']->render('index.html.twig', []);
})->bind('index');
*
*/

$app->run();
