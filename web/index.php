<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use util\app;

// development server
$filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$app = require_once __DIR__ . '/../app.php';

$app['overall_domain'] = getenv('OVERALL_DOMAIN');

$secure = $app['controllers_factory'];

$secure->host('l.' . $app['overall_domain']);
	//->when('request.isSecure() == true')

$secure->get('/', function(Request $request){
	return 'secured ' . $request->getHost() . ' ' . $request;
});

$users = $app['controllers_factory'];

$users->get('/{type}', 'controller\\users::index')->bind('users_index');
$users->get('/{id}')->

$public = $app['controllers_factory'];

$public->host('x.' . $app['overall_domain']);

$public->get('/', function(){
	return 'public';
});







$app->mount('/', $secure);
$app->mount('/', $public);



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
