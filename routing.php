<?php

use util\app;
use Symfony\Component\HttpFoundation\Request;

$cc = $app['controllers_factory'];

$cc->match('/login', 'controller\\login::form')
	->bind('login');
$cc->match('/password-reset', 'controller\\password_reset::form')
	->bind('password_reset');
$cc->match('/password-reset/{token}', 'controller\\password_reset::new_password')
	->bind('password_reset_new_password');
$cc->get('/logout', 'controller\\logout::logout')
	->bind('logout');
$cc->match('/register', 'controller\\register::form')
	->bind('register');
$cc->get('/register/{token}', 'controller\\register::confirm')
	->bind('register_confirm');
$cc->match('/contact', 'controller\\contact::form')
	->bind('contact');
$cc->get('/contact/{token}', 'controller\\contact::confirm')
	->bind('contact_confirm');
$cc->get('/{page}', 'controller\\page::show')
	->value('page', 'index')
	->assert('page', '|[a-z0-9-]{3,}')
	->convert('page', 'page_converter:get')
	->bind('page_show');

$cc->get('/pop.php', function(Request $request, app $app, $schema){
	return $app->render('pop pop ' . $schema);
});

$acc = $app['controllers_factory'];

/**
 * ads (elas:messages)
 */

$ad = $app['controllers_factory'];

$ad->get('/', 'controller\\ad::index')
	->bind('ad_index');
$ad->get('/self', 'controller\\ad::show_self')
	->bind('ad_self');
$ad->get('/{ad}', 'controller\\ad::show')
	->convert('ad', 'ad_converter:get')
	->bind('ad_show');
$ad->match('/add', 'controller\\ad::add')
	->bind('ad_add');
$ad->match('/{ad}/edit', 'controller\\ad::edit')
	->convert('ad', 'ad_converter:get')
	->bind('ad_edit');

$ad->assert('ad', '\d+');

$acc->mount('/ads', $ad);

/*
 * users
 */

$user = $app['controllers_factory'];

$user->get('/{user_type}', 'controller\\user::index')
	->value('user_type', 'active')
	->bind('user_index');

$user->get('/self', 'controller\\user::show_self')
	->bind('user_self');
$user->get('/{user_type}/{user}', 'controller\\user::show')
	->convert('user', 'user_converter:get')
	->bind('user_show');

$user->get('/{user_type}/map', 'controller\\user::map')
	->value('user_type', 'active')
	->bind('user_map');

$user->get('/{user_type}/tile', 'controller\\user::tile')
	->value('user_type', 'active')
	->bind('user_tile');
$user->match('/add', 'controller\\user::add')
	->bind('user_add');
$user->match('/{user}/edit', 'controller\\user::edit')
	->convert('user', 'user_converter:get')
	->bind('user_edit');
$user->get('/typeahead/{user_type}', 'controller\\user_typeahead::get')
	->bind('user_typeahead');
$user->get('/typeahead-interlets/{user}', 
	'controller\\user_typeahead::get_interlets')
//	->convert('user', 'user_converter:get')
	->bind('user_interlets_typeahead');
$user->get('/weighted-balance/{user}/{days}', 
	'controller\\user::weighted_balance')
	->assert('days', '\d+')
//	->convert('user', 'user_converter:get')
	->bind('user_weighted_balance');

$user->assert('user_type', 'active|new|leaving|direct|interlets|pre-active|post-active|all')
	->assert('user', '\d+');

$acc->mount('/users', $user);

/**
 * images
 */

$img = $app['controllers_factory'];

$img->post('/', 'controller\\img::create')
	->bind('img_create');
$img->delete('/{img}', 'controller\\img::del')
	->convert('img', 'service\\xdb::get')
	->bind('img_del');
$img->match('/{ad}/del', 'controller\\acccount::del_form')
	->assert('ad', '[a-z0-9][a-z0-9-]{10}[a-z0-9]')
	->convert('ad', 'service\\xdb::get')
	->bind('img_del_form');

$img->assert('img', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

$acc->mount('/images', $img);

/*
 *  users  (elas:login part of users)
 */

 /*
$user = $app['controllers_factory'];

$user->get('/', 'controller\\user::index')
	->bind('user_index');
$user->get('/self', 'controller\\user::show_self')
	->bind('user_self');
$user->get('/{user}', 'controller\\user::show')
	->convert('user', 'service\\xdb::get')
	->bind('user_show');
$user->match('/add', 'controller\\user::add')
	->bind('user_add');
$user->match('/{user}/edit', 'controller\\user::edit')
	->convert('user', 'service\\xdb::get')
	->bind('user_edit');

$user->assert('user', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

$acc->mount('/users', $user);
*/


/*
 *  transactions
 */

$transaction = $app['controllers_factory'];

$transaction->get('/', 'controller\\transaction::index')
	->bind('transaction_index');
$transaction->get('/self', 'controller\\transaction::show_self')
	->bind('transaction_self');
$transaction->get('/{transaction}', 'controller\\transaction::show')
	->convert('transaction', 'transaction_converter:get')
	->bind('transaction_show');
$transaction->match('/add', 'controller\\transaction::add')
	->bind('transaction_add');
$transaction->get('/{transaction}/edit', 'controller\\transacion::edit')
	->convert('transaction', 'transaction_converter:get')
	->bind('transaction_edit');
$transaction->match('/add-interlets', 'controller\\transaction::add_interlets')
	->bind('transaction_add_interlets');
$transaction->match('/{transaction}/edit', 'controller\\transaction::edit')
	->bind('transaction_edit');
$transaction->get('/plot-user/{user}/{days}', 'controller\\transaction::plot_user')
	->assert('user', '\d+')
	->assert('days', '\d+')
	->value('days', 365)
	->bind('transaction_plot_user');
$transaction->get('/sum-in/{days}', 'controller\\transaction::sum_in')
	->assert('days', '\d+')
	->value('days', 365)
	->bind('transaction_sum_in');
$transaction->get('/sum-out/{days}', 'controller\\transaction::sum_out')
	->assert('days', '\d+')
	->value('days', 365)
	->bind('transaction_sum_out');

$transaction->assert('transaction', '\d+');

$acc->mount('/transactions', $transaction);

/*
 * news
 */

$news = $app['controllers_factory'];

$news->get('/', 'controller\\news::index')
	->bind('news_index');
$news->get('/{news}', 'controller\\news::show')
	->convert('news', 'news_converter:get')
	->bind('news_show');
$news->match('/add', 'controller\\news::add')
	->bind('news_add');
$news->match('/{news}/edit', 'controller\\news::edit')
	->convert('news', 'news_converter:get')
	->bind('news_edit');
$news->post('/{news}/approve', 'controller\\news::approve')
	->convert('news', 'news_converter:get')
	->bind('news_approve');

$news->assert('news', '\d+');

$acc->mount('/news', $news);

/*
 *  docs
 */

$doc = $app['controllers_factory'];

$doc->get('/', 'controller\\doc::index')
	->bind('doc_index');
$doc->get('/{doc}', 'controller\\doc::show')
	->convert('doc', 'doc_converter:get')
	->bind('doc_show');
$doc->match('/add', 'controller\\doc::add')
	->bind('doc_add');
$doc->match('/{doc}/edit', 'controller\\doc::edit')
	->convert('doc', 'doc_converter:get')
	->bind('doc_edit');
$doc->get('/typeahead', 'controller\\doc::typeahead')
	->bind('doc_typeahead');

$doc->assert('doc', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

$acc->mount('/docs', $doc);

/*
 *  forum
 */

$forum = $app['controllers_factory'];

$forum->get('/', 'controller\\forum::index')
	->bind('forum_index');
$forum->get('/{forum}', 'controller\\forum::show')
	->convert('forum', 'forum_converter:get')
	->bind('forum_show');
$forum->match('/add', 'controller\\forum::add')
	->bind('forum_add');
$forum->match('/{forum}/edit', 'controller\\forum::edit')
	->convert('forum', 'forum_converter:get')
	->bind('forum_edit');

$forum->assert('forum', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

$acc->mount('/forum', $forum);

$acc->assert('access', '[giua]');
$cc->mount('/{access}', $acc);


/*
 * elas
 */

$ua = $app['controllers_factory'];

$elas = $app['controllers_factory'];

$elas->get('/soap-status/{user}', 'controller\\elas::soap_status')
	->convert('user', 'user_converter:get')
	->bind('elas_soap_status');
$elas->get('/group-login/{user}', 'controller\\elas::soap_status')
	->convert('user', 'user_converter:get')
	->bind('elas_group_login');

$elas->assert('user', '\d+');

$ua->mount('/elas', $elas);



$app->mount('/{_locale}/{schema}/{access}/notifications', new provider\notification_controller_provider());

$app->mount('/{_locale}/{schema}/{access}/support', new provider\support_controller_provider());

/**
 * a (admin)
 */

$app->mount('/{_locale}/{schema}/{access}/status', new provider\status_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/pages', new provider\page_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/permissions', new provider\permission_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/categories', new provider\category_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/custom-fields', new provider\custom_field_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/contact-types', new provider\type_contact_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/contact-details', new provider\contact_detail_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/config', new provider\config_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/export', new provider\export_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/auto-min-limit', new provider\auto_min_limit_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/mass-transaction', new provider\mass_transaction_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/periodic-charge', new provider\periodic_charge_controller_provider());
$app->mount('/{_locale}/{schema}/{access}/logs', new provider\log_controller_provider());

//

/*
$cc->before(function(Request $request) use ($app){

	$schema = $request->attributes->get('_route_params')['schema'];

	if (!isset($schema) || !$app['schemas']->is_set($schema))
	{
		$app->abort(404, 'error.404_page_not_found');
	}

	$app['db']->exec('set search_path to ' . $schema);

	$app['schema'] = $schema;
});
*/

$cc->assert('schema', '[a-z][a-z0-9]*');

$app->mount('/{schema}/{_locale}', $cc);

$register = $app['controllers_factory'];
$register->match('/', 'controller\\register::index')
	->bind('register');
$register->get('/{token}', 'controller\\register::token')
	->bind('register_token');

$public = $app['controllers_factory'];

$public->get('/', function() use ($app){
	return 'public ' . $app['url_generator']->generate('main_index');
});

$app->mount('/', $public);

$app->match('/hosting-request', 'controller\\hosting_request::form')
	->bind('hosting_request');
$app->get('/hosting-request/{token}', 'controller\\hosting_request::confirm')
	->bind('hosting_request_confirm');
$app->get('/monitor', 'controller\\main::monitor')
	->bind('monitor');
$app->get('/', 'controller\\main::index')
	->bind('main_index');

