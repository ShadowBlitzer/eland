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


/*

$app['overall_domain'] = getenv('OVERALL_DOMAIN');

$secure = $app['controllers_factory'];

$secure->get('/', function(Request $request){
	return 'secured ' . $request->getHost() . ' ' . $request;
});

$users = $app['controllers_factory'];

$users->assert('user_type', '^(\'active|new|leaving|intertrade|pre-active|post-active|all\')$')
	->assert('user', '\d+')
	->convert('user', 'service\\user_cache::get');

$users->match('/add', 'controller\\user::add')->bind('user_add');   // admin
$users->match('/{user}/edit', 'controller\\user::edit')->bind('user_edit'); // +admin
$users->match('/{user}/del', 'controller\\user::del')->bind('user_del');   // admin

$users->get('/{user_type}/{user}', 'controller\\user::show')->bind('user_show');
$users->get('/{user}', 'controller\\user::show_without_type_context')->bind('user_show_without_type_context');
$users->get('/', 'controller\\user::index');



$register = $app['controllers_factory'];
$register->match('/', 'controller\\register::index')->bind('register');
$register->get('/{token}', 'controller\\register::token')
	->bind('register_token');




$public = $app['controllers_factory'];

$public->host('x.' . $app['overall_domain']);

$public->get('/', function(){
	return 'public';
});







$app->mount('/', $secure);
$app->mount('/', $public);
*/

$app->match('/hosting-request', 'controller\\main::hosting_request')->bind('hosting_request');
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
