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
	->assert('token', '[a-z0-9][a-z0-9-]{14,14}[a-z0-9]');

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

$cc->get('/', function (Request $request, app $app, $schema){

	return ' ok test ' . $schema;
});

$acc = $app['controllers_factory'];

/**
 * ads (elas:messages)
 */

$ad = $app['controllers_factory'];

$ad->get('/', 'controller\\ad::index')
	->bind('ad_index');
$ad->get('/{ad}', 'controller\\ad::show')
	->convert('ad', 'service\\xdb::get')
	->bind('ad_show');
$ad->match('/add', 'controller\\ad::add')
	->bind('ad_add');
$ad->match('/{ad}/edit', 'controller\\ad::edit')
	->convert('ad', 'service\\xdb::get')
	->bind('ad_edit');

$ad->assert('ad', '[a-z0-9][a-z0-9-]{6}[a-z0-9]');

$acc->mount('/ads', $ad);

/*
 * accounts (elas:users)
 */

$account = $app['controllers_factory'];

$account->get('/{account_type}', 'controller\\account::index')
	->value('account_type', 'active')
	->bind('account_index');
$account->get('/{account_type}/{account}', 'controller\\account::show')
	->convert('account', 'service\\xdb::get')
	->bind('account_show');

$account->get('/{account_type}/map', 'controller\\account::map')
	->value('account_type', 'active')
	->bind('account_map');

$account->get('/{account_type}/tile', 'controller\\account::tile')
	->value('account_type', 'active')
	->bind('account_tile');
$account->match('/add', 'controller\\acccount::add')
	->bind('account_add');
$account->match('/{account}/edit', 'controller\\account::edit')
	->convert('account', 'service\\xdb::get')
	->bind('account_edit');
$account->get('/typeahead/{account_type}', 'controller\\account::typeahead')
	->bind('account_typeahead_self');
$account->get('/typeahead/{account}/{account_type}', 'controller\\account::typeahead')
	->assert('account', '[a-z0-9][a-z0-9-]{6}[a-z0-9]')
	->convert('account', 'service\\xdb::get')
	->bind('account_typeahead');
$account->get('/weighted-balance/{account}/{days}', 'controller\\account::weighted_balance')
	->assert('account', '[a-z0-9][a-z0-9-]{6}[a-z0-9]')
	->assert('days', '/d+')
	->convert('account', 'service\\xdb::get')
	->bind('account_typeahead');

$account->assert('account_type', 'active|new|leaving|interlets|pre-active|post-active|all')
	->assert('account', '[a-z0-9][a-z0-9-]{6}[a-z0-9]');

$acc->mount('/accounts', $account);

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
	->assert('ad', '[a-z0-9][a-z0-9-]{6}[a-z0-9]')
	->convert('ad', 'service\\xdb::get')
	->bind('img_del_form');

$img->assert('img', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

$acc->mount('/imgs', $img);

/*
 *  users  (elas:login part of users)
 */

$user = $app['controllers_factory'];

$user->get('/', 'controller\\user::index')
	->bind('user_index');
$user->get('/{user}', 'controller\\user::show')
	->convert('user', 'service\\xdb::get')
	->bind('user_show');
$user->match('/add', 'controller\\user::add')
	->bind('user_add');
$user->match('/{user}/edit', 'controller\\user::edit')
	->convert('user', 'service\\xdb::get')
	->bind('user_edit');

$user->assert('user', '[a-z0-9][a-z0-9-]{6}[a-z0-9]');

$acc->mount('/users', $user);

/*
 *  transactions
 */

$transaction = $app['controllers_factory'];

$transaction->get('/', 'controller\\transaction::index')
	->bind('transaction_index');
$transaction->get('/{transaction}', 'controller\\transacion::show')
	->convert('transaction', 'service\\xdb::get')
	->bind('transaction_show');
$transaction->match('/add', 'controller\\transaction::add')
	->bind('transaction_add');
$transaction->match('/{transaction}/edit', 'controller\\transaction::edit')
	->bind('transaction_edit');
$transaction->get('/plot-account/{account}/{days}', 'controller\\transaction::plot_account')
	->assert('account', '[a-z0-9][a-z0-9-]{6}[a-z0-9]')
	->assert('days', '\d+')
	->value('days', 365)
	->bind('transaction_plot_account');
$transaction->get('/sum-in/{days}', 'controller\\transaction::sum_in')
	->assert('days', '\d+')
	->value('days', 365)
	->bind('transaction_sum_in');
$transaction->get('/sum-out/{days}', 'controller\\transaction::sum_out')
	->assert('days', '\d+')
	->value('days', 365)
	->bind('transaction_sum_out');

$transaction->assert('transaction', '^[a-z0-9][a-z0-9-]{8}[a-z0-9]$');

$acc->mount('/transactions', $transaction);

/*
 * news
 */

$news = $app['controllers_factory'];

$news->get('/', 'controller\\news::index')
	->bind('news_index');
$news->get('/{news}', 'controller\\news::show')
	->convert('news', 'service\\xdb::get')
	->bind('news_show');
$news->match('/add', 'controller\\news::add')
	->bind('news_add');
$news->match('/{news}/edit', 'controller\\news::edit')
	->convert('news', 'service\\xdb::get')
	->bind('news_edit');
$news->post('/{news}/approve', 'controller\\news::approve')
	->convert('news', 'service\\xdb::get')
	->bind('news_approve');

$news->assert('news', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$acc->mount('/news', $news);

/*
 *  docs
 */

$doc = $app['controllers_factory'];

$doc->get('/', 'controller\\doc::index')
	->bind('doc_index');
$doc->get('/{doc}', 'controller\\doc::show')
	->convert('doc', 'service\\xdb::get')
	->bind('doc_show');
$doc->match('/add', 'controller\\doc::add')
	->bind('doc_add');
$doc->match('/{doc}/edit', 'controller\\doc::edit')
	->convert('doc', 'service\\xdb::get')
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
	->convert('forum', 'service\\xdb::get')
	->bind('forum_show');
$forum->match('/add', 'controller\\forum::add')
	->bind('forum_add');
$forum->match('/{forum}/edit', 'controller\\forum::edit')
	->convert('forum', 'service\\xdb::get')
	->bind('forum_edit');

$forum->assert('forum', '[a-z0-9][a-z0-9-]{6}[a-z0-9]');

$acc->mount('/forum', $forum);

$acc->assert('access', '[giua]');
$cc->mount('/{access}', $acc);

/*
 * elas
 */

$ua = $app['controllers_factory'];

$elas = $app['controllers_factory'];

$elas->get('/soap-status/{account}', 'controller\\elas::soap_status')
	->convert('account', 'service\\xdb::get')
	->bind('elas_soap_status');
$elas->get('/group-login/{account}', 'controller\\elas::soap_status')
//	->convert('account', 'service\\xdb::get')
	->bind('elas_group_login');

$elas->assert('account', '[a-z0-9][a-z0-9-]{6}[a-z0-9]');

$ua->mount('/elas', $elas);

/**
 * support
 */

$ua->match('/support', 'controller\\support::form')
	->bind('support');



//

$ua->assert('access', '[ua]');

$cc->mount('/{access}', $ua);

/**
 * a (admin)
 */

$a = $app['controllers_factory'];

$a->get('/status', 'controller\\status::get')
	->bind('status');

/**
 * permissions
 */

$pms = $app['controllers_factory'];

$pms->get('/', 'controller\\permission::index')
	->bind('permission_index');

$a->mount('/permissions', $pms);

/**
 * categories
 */

$cat = $app['controllers_factory'];

$cat->get('/', 'controller\\category::index')
	->bind('elas_soap_status');
$cat->match('/add', 'controller\\category::add')
	->bind('category_add');
$cat->match('/{category}/edit', 'controller\\category::edit')
	->convert('category', 'service\\xdb::get')
	->bind('category_edit');

$a->mount('/categories', $cat);

/**
 * Contact types
 */

$contact_type = $app['controllers_factory'];

$contact_type->get('/', 'controller\\contact_type::index')
	->bind('contact_type_index');
$contact_type->match('/add', 'controller\\contact_type::add')
	->bind('contact_type_add');
$contact_type->match('/{contact_type}/edit', 'controller\\contact_type::add')
	->assert('contact_type', '[a-z0-9][a-z0-9-]{6}[a-z0-9]')
	->convert('contact_type', 'service\\xdb::get')
	->bind('contact_type_add');

$a->mount('/contact_types', $contact_type);

/**
 * Contacts
 */


/**
 * Config (includes autominlimit)
 */

$config = $app['controllers_factory'];

$config->match('/', 'controller\\config::index')
	->bind('config_index');

$a->mount('/config', $config);

//

$a->get('/groups/typeahead', 'controller\\group::typeahead')
	->bind('group_typeahead');

/**
 * export
 */

$export = $app['controllers_factory'];

$export->get('/', 'controller\\export::index')
	->bind('export_index');

$a->mount('/export', $export);

/**
 * auto min limit
 */

$a->match('/autominlimit', 'controller\\autominlimit::form')
	->bind('autominlimit');

/*
 * mass_transaction
 */

$a->match('/mass-transaction', 'controller\\mass_transaction::form')
	->bind('mass_transaction');

/*
 * logs
 */

$a->get('/logs', 'controller\\log::index')
	->bind('log');
$a->get('/logs/typeahead', 'controller\\log::typeahead')
	->bind('log_typeahead');

$cc->mount('/a', $a);

//

$cc->before(function(Request $request) use ($app){

	$schema = $request->attributes->get('_route_params')['schema'];

	if (!isset($schema) || !$app['schemas']->is_set($schema))
	{
		$app->abort(404, 'error.404_page_not_found');
	}

	$app['db']->exec('set search_path to ' . $schema);

	$app['schema'] = $schema;
});

$app->mount('/{schema}', $cc);

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

$app->run();
