<?php

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

$cc->get('/', function (Request $request, app $app, $schema){
	return ' ok test ' . $schema;
});

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
	->convert('ad', 'service\\xdb::get')
	->bind('ad_show');
$ad->match('/add', 'controller\\ad::add')
	->bind('ad_add');
$ad->match('/{ad}/edit', 'controller\\ad::edit')
	->convert('ad', 'service\\xdb::get')
	->bind('ad_edit');

$ad->assert('ad', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

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
//	->convert('user', 'service\\xdb::get')
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
//	->convert('user', 'service\\xdb::get')
	->bind('user_edit');
$user->get('/typeahead/{user_type}', 'controller\\user_typeahead::get')
	->bind('user_typeahead_self');
$user->get('/typeahead/{user}', 'controller\\user_typeahead::get_interlets')
//	->convert('user', 'service\\xdb::get')
	->bind('user_typeahead_interlets');
$user->get('/weighted-balance/{user}/{days}', 'controller\\user::weighted_balance')
	->assert('days', '\d+')
//	->convert('user', 'service\\xdb::get')
	->bind('user_typeahead');

$user->assert('user_type', 'active|new|leaving|interlets|pre-active|post-active|all')
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
$transaction->get('/{transaction}', 'controller\\transacion::show')
	->convert('transaction', 'service\\xdb::get')
	->bind('transaction_show');
$transaction->match('/add', 'controller\\transaction::add')
	->bind('transaction_add');
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

$news->assert('news', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

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

$forum->assert('forum', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

$acc->mount('/forum', $forum);

/*
* notifications
*/


$notification = $app['controllers_factory'];

$notification->get('/', 'controller\\notification::index')
	->bind('notification_index');
$notification->get('/self', 'controller\\notification::show_self')
	->bind('notification_self');
$notification->get('/{notification}', 'controller\\notification::show')
	->convert('notification', 'service\\xdb::get')
	->bind('notification_show');
$notification->match('/add', 'controller\\notification::add')
	->bind('notification_add');
$notification->match('/{notification}/edit', 'controller\\notification::edit')
	->convert('notification', 'service\\xdb::get')
	->bind('notification_edit');

$notification->assert('notification', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

$acc->mount('/notifications', $notification);


$acc->assert('access', '[giua]');
$cc->mount('/{access}', $acc);

/*
 * elas
 */

$ua = $app['controllers_factory'];

$elas = $app['controllers_factory'];

$elas->get('/soap-status/{user}', 'controller\\elas::soap_status')
	->convert('user', 'service\\xdb::get')
	->bind('elas_soap_status');
$elas->get('/group-login/{user}', 'controller\\elas::soap_status')
//	->convert('user', 'service\\xdb::get')
	->bind('elas_group_login');

$elas->assert('user', '\d+');

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

$a->get('/status', 'controller\\status::index')
	->bind('status');

/**
 * permissions
 */

$pms = $app['controllers_factory'];

$pms->get('/', 'controller\\permission::index')
	->bind('permission');

$a->mount('/permissions', $pms);

/**
 * categories
 */

$cat = $app['controllers_factory'];

$cat->get('/', 'controller\\category::index')
	->bind('category_index');
$cat->match('/add/{parent_category}', 'controller\\category::add')
	->value('parent_category', 0)
	->assert('parent_category', '\d+')
	->bind('category_add');
$cat->match('/{category}/edit', 'controller\\category::edit')
//	->convert('category', 'service\\xdb::get')
	->assert('category', '\d+')
	->bind('category_edit');
$cat->match('/{category}/del', 'controller\\category::del')
	->assert('category', '\d+')
//	->convert('category', 'service\\xdb::get')
	->bind('category_del');

$a->mount('/categories', $cat);

/**
 * custom fields
 */

$cust = $app['controllers_factory'];

$cust->get('/', 'controller\\custom_field::index')
	->bind('custom_field_index');
$cust->match('/add', 'controller\\custom_field::add')
	->bind('custom_field_add');
$cust->match('/{custom_field}/edit', 'controller\\custom_field::edit')
	->convert('custom_field', 'service\\xdb::get')
	->bind('custom_field_edit');

$a->mount('/custom-fields', $cust);

/**
 * Contact types
 */

$type_contact = $app['controllers_factory'];

$type_contact->get('/', 'controller\\type_contact::index')
	->bind('typecontact_index');
$type_contact->match('/add', 'controller\\type_contact::add')
	->bind('typecontact_add');
$type_contact->match('/{type_contact}/edit', 'controller\\type_contact::edit')
	->assert('type_contact', '\d+')
//	->convert('type_contact', 'service\\xdb::get')
	->bind('typecontact_edit');
$type_contact->match('/{type_contact}/del', 'controller\\type_contact::del')
	->assert('type_contact', '\d+')
//	->convert('type_contact', 'service\\xdb::get')
	->bind('typecontact_del');

$a->mount('/contact-types', $type_contact);

/**
 * Contacts (temp)
 */

$contact_detail = $app['controllers_factory'];

$contact_detail->get('/', 'controller\\contact_detail::index')
	->bind('contactdetail_index');
$contact_detail->match('/add', 'controller\\contact_detail::add')
	->bind('contactdetail_add');
$contact_detail->match('/{contact_detail}/edit', 'controller\\contact_detail::edit')
	->assert('contact_detail', '[a-z0-9][a-z0-9-]{10}[a-z0-9]')
	->convert('contact_detail', 'service\\xdb::get')
	->bind('contactdetail_add');

$a->mount('/contact-details', $contact_detail);


/**
 * Config (includes autominlimit)
 */

$config = $app['controllers_factory'];

$config->match('/', 'controller\\config::index')
	->bind('config_index');
$config->match('/balance-limits', 'controller\\config::balance_limits')
	->bind('config_balance_limits');
$config->match('/ads', 'controller\\config::ads')
	->bind('config_ads');
$config->match('/naming', 'controller\\config::naming')
	->bind('config_naming');
$config->match('/mail-addresses', 'controller\\config::mail_addresses')
	->bind('config_mail_addresses');
$config->match('/periodic-mail', 'controller\\config::periodic_mail')
	->bind('config_periodic_mail');
$config->match('/contact-form', 'controller\\config::contact_form')
	->bind('config_contact_form');
$config->match('/registration-form', 'controller\\config::registration_form')
	->bind('config_registration_form');
$config->match('/forum', 'controller\\config::forum')
	->bind('config_forum');
$config->match('/members', 'controller\\config::members')
	->bind('config_members');
$config->match('/system', 'controller\\config::system')
	->bind('config_system');

$a->mount('/config', $config);

//

$a->get('/groups/typeahead', 'controller\\group::typeahead')
	->bind('group_typeahead');

/**
 * export
 */

$export = $app['controllers_factory'];

$export->match('/', 'controller\\export::index')
	->bind('export');

$a->mount('/export', $export);

/**
 * auto min limit
 */

$a->match('/auto-min-limit', 'controller\\auto_min_limit::form')
	->bind('auto_min_limit');

/*
 * mass_transaction
 */

$a->match('/mass-transaction', 'controller\\mass_transaction::form')
	->bind('mass_transaction');

/*
 * periodic charge
 */

$a->match('/periodic-charge', 'controller\\periodic_charge::form')
	->bind('periodic_charge');
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

$cc->assert('schema', '[a-z][a-z0-9]*');

$app->mount('/{schema}', $cc);

$register = $app['controllers_factory'];
$register->match('/', 'controller\\register::index')->bind('register');
$register->get('/{token}', 'controller\\register::token')
	->bind('register_token');

$public = $app['controllers_factory'];

$public->get('/', function() use ($app){
	return 'public ' . $app['url_generator']->generate('main_index');
});

$app->mount('/', $public);

$app->match('/hosting-request', 'controller\\main::hosting_request')->bind('hosting_request');
$app->get('/monitor', 'controller\\main::monitor')->bind('monitor');
$app->get('/', 'controller\\main::index')->bind('main_index');
