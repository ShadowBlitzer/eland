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

$g_acc = $app['controllers_factory'];
$i_acc = $app['controllers_factory'];
$u_acc = $app['controllers_factory'];
$a_acc = $app['controllers_factory'];

/**
 * messages
 */
// g

$g_message = $app['controllers_factory'];

$g_message->get('/', 'controller\\message::g_index')
	->bind('g_message_index');
$g_message->get('/{message}', 'controller\\message::show')
	->convert('message', 'service\\xdb::get')
	->bind('g_message_show');

$g_message->assert('message', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$g_acc->mount('/offers-and-wants', $g_message);

// i

$i_message = $app['controllers_factory'];

$i_message->get('/', 'controller\\message::i_index')
	->bind('i_message_index');
$i_message->get('/{message}', 'controller\\message::show')
	->convert('message', 'service\\xdb::get')
	->bind('i_message_show');
$i_message->match('/add', 'controller\\message::add')
	->bind('i_message_add');
$i_message->match('/{message}/edit', 'controller\\message::i_edit')
	->convert('message', 'service\\xdb::get')
	->bind('i_message_edit');

$i_message->assert('message', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$i_acc->mount('/offers-and-wants', $i_message);

// u

$u_message = $app['controllers_factory'];

$u_message->get('/', 'controller\\message::u_index')
	->bind('u_message_index');
$u_message->get('/{message}', 'controller\\message::show')
	->convert('message', 'service\\xdb::get')
	->bind('u_message_show');
$u_message->match('/add', 'controller\\message::add')
	->bind('u_message_add');
$u_message->match('/{message}/edit', 'controller\\message::u_edit')
	->convert('message', 'service\\xdb::get')
	->bind('u_message_edit');

$u_message->assert('message', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$u_acc->mount('/offers-and-wants', $u_message);

// a

$a_message = $app['controllers_factory'];

$a_message->get('/', 'controller\\message::a_index')
	->bind('a_message_index');
$a_message->get('/{message}', 'controller\\message::show')
	->convert('message', 'service\\xdb::get')
	->bind('a_message_show');
$a_message->match('/add', 'controller\\message::add')
	->bind('a_message_add');
$a_message->match('/{message}/edit', 'controller\\message::a_edit')
	->convert('message', 'service\\xdb::get')
	->bind('a_message_edit');

$a_message->assert('message', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$a_acc->mount('/offers-and-wants', $a_message);

/*
 * accounts
 */
// g

$g_account = $app['controllers_factory'];

$g_account->get('/{account_type}', 'controller\\account::g_index')
	->value('account_type', 'active')
	->bind('g_account_index');
$g_account->get('/{account_type}/{account}', 'controller\\account::g_show')
	->convert('account', 'service\\xdb::get')
	->bind('g_account_show');

$g_account->assert('account_type', '^(\'active|new|leaving\')$')
	->assert('account', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$g_acc->mount('/accounts', $g_account);

// i

$i_account = $app['controllers_factory'];

$i_account->get('/{account_type}', 'controller\\account::i_index')
	->value('account_type', 'active')
	->bind('i_account_index');
$i_account->get('/{account_type}/{account}', 'controller\\account::i_show')
	->convert('account', 'service\\xdb::get')
	->bind('i_account_show');
$i_account->match('/{account}/edit', 'controller\\account::i_edit')
	->convert('account', 'service\\xdb::get')
	->bind('i_account_edit');

$i_account->assert('account_type', '^(\'active|new|leaving\')$')
	->assert('account', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$i_acc->mount('/accounts', $i_account);

// u

$u_account = $app['controllers_factory'];

$u_account->get('/{account_type}', 'controller\\account::u_index')
	->value('account_type', 'active')
	->bind('u_account_index');
$u_account->get('/{account_type}/{account}', 'controller\\account::u_show')
	->convert('account', 'service\\xdb::get')
	->bind('u_account_show');
$u_account->match('/{account}/edit', 'controller\\account::u_edit')
	->convert('account', 'service\\xdb::get')
	->bind('u_account_edit');

$u_account->assert('account_type', '^(\'active|new|leaving|interlets\')$')
	->assert('account', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$u_acc->mount('/accounts', $u_account);

// a

$a_account = $app['controllers_factory'];

$a_account->get('/{account_type}', 'controller\\account::a_index')
	->value('account_type', 'active')
	->bind('a_account_index');
$a_account->get('/{account_type}/{account}', 'controller\\account::a_show')
	->convert('account', 'service\\xdb::get')
	->bind('a_account_show');
$a_account->match('/add', 'controller\\acccount::a_add')
	->bind('a_account_add');
$a_account->match('/{account}/edit', 'controller\\account::a_edit')
	->convert('account', 'service\\xdb::get')
	->bind('a_account_edit');

$a_account->assert('account_type', '^(\'active|new|leaving|interlets|pre-active|post-active|all\')$')
	->assert('account', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$a_acc->mount('/accounts', $a_account);

/**
 * images
 */

// u

$u_img = $app['controllers_factory'];

$u_img->post('/', 'controller\\img::u_create')
	->bind('u_img_create');
$u_img->delete('/{img}', 'controller\\img::u_del')
	->convert('img', 'service\\xdb::get')
	->bind('u_img_del');
$u_img->match('/{message}/del', 'controller\\img::u_del_form')
	->assert('message', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$')
	->convert('message', 'service\\xdb::get')
	->bind('u_img_del_form');

$u_img->assert('img', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

$u_acc->mount('/imgs', $u_img);

// a

$a_img = $app['controllers_factory'];

$a_img->post('/', 'controller\\img::a_create')
	->bind('a_img_create');
$a_img->delete('/{img}', 'controller\\img::a_del')
	->convert('img', 'service\\xdb::get')
	->bind('a_img_del');
$a_img->match('/{message}/del', 'controller\\acccount::a_del_form')
	->assert('message', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$')
	->convert('message', 'service\\xdb::get')
	->bind('a_img_del_form');

$a_img->assert('img', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

$a_acc->mount('/imgs', $a_img);

/*
 *  users
 */
// a

$a_user = $app['controllers_factory'];

$a_user->get('/', 'controller\\user::a_index')
	->bind('a_user_index');
$a_user->get('/{user}', 'controller\\user::a_show')
	->convert('user', 'service\\xdb::get')
	->bind('a_user_show');
$a_user->match('/add', 'controller\\user::a_add')
	->bind('a_user_add');
$a_user->match('/{user}/edit', 'controller\\user::a_edit')
	->convert('user', 'service\\xdb::get')
	->bind('a_user_edit');

$a_user->assert('user', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$a_acc->mount('/users', $a_user);


/*
 *  transactions
 */

//g

$g_transaction = $app['controllers_factory'];

$g_transaction->get('/', 'controller\\g_transaction::g_index')
	->bind('g_transaction_index');
$g_transaction->get('/{transaction}', 'controller\\transacion::g_show')
	->convert('transaction', 'service\\xdb::get')
	->bind('g_transaction_show');

$g_transaction->assert('transaction', '^[a-z0-9][a-z0-9-]{8}[a-z0-9]$');

$g_acc->mount('/transactions', $g_transaction);

//i

$i_transaction = $app['controllers_factory'];

$i_transaction->get('/', 'controller\\transaction::i_index')
	->bind('i_transaction_index');
$i_transaction->get('/{transaction}', 'controller\\transacion::i_show')
	->convert('transaction', 'service\\xdb::get')
	->bind('i_transaction_show');
$i_transaction->match('/add', 'controller\\transaction::i_add')
	->bind('i_transaction_add');

$i_transaction->assert('transaction', '^[a-z0-9][a-z0-9-]{8}[a-z0-9]$');

$i_acc->mount('/transactions', $i_transaction);

//u

$u_transaction = $app['controllers_factory'];

$u_transaction->get('/', 'controller\\transaction::u_index')
	->bind('u_transaction_index');
$u_transaction->get('/{transaction}', 'controller\\transacion::u_show')
	->convert('transaction', 'service\\xdb::get')
	->bind('u_transaction_show');
$u_transaction->match('/add', 'controller\\transaction::u_add')
	->bind('u_transaction_add');

$u_transaction->assert('transaction', '^[a-z0-9][a-z0-9-]{8}[a-z0-9]$');

$u_acc->mount('/transactions', $u_transaction);

//a

$a_transaction = $app['controllers_factory'];

$a_transaction->get('/', 'controller\\transaction::a_index')
	->bind('a_transaction_index');
$a_transaction->get('/{transaction}', 'controller\\transacion::a_show')
	->convert('transaction', 'service\\xdb::get')
	->bind('a_transaction_show');
$a_transaction->match('/add', 'controller\\transaction::a_add')
	->bind('a_transaction_add');
$a_transaction->match('/{transaction}/edit', 'controller\\transaction::a_edit')
	->bind('a_transaction_edit');

$a_transaction->assert('transaction', '^[a-z0-9][a-z0-9-]{8}[a-z0-9]$');

$a_acc->mount('/transactions', $a_transaction);

/*
 * news
 */

// g
$g_news = $app['controllers_factory'];

$g_news->get('/', 'controller\\news::g_index')
	->bind('g_news_index');
$g_news->get('/{news}', 'controller\\news::g_show')
	->convert('news', 'service\\xdb::get')
	->bind('g_news_show');

$g_news->assert('news', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$g_acc->mount('/news', $g_news);

// i
$i_news = $app['controllers_factory'];

$i_news->get('/', 'controller\\news::i_index')
	->bind('i_news_index');
$i_news->get('/{news}', 'controller\\news::i_show')
	->convert('news', 'service\\xdb::get')
	->bind('i_news_show');

$i_news->assert('news', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$i_acc->mount('/news', $i_news);

// u
$u_news = $app['controllers_factory'];

$u_news->get('/', 'controller\\news::u_index')
	->bind('u_news_index');
$u_news->get('/{news}', 'controller\\news::u_show')
	->convert('news', 'service\\xdb::get')
	->bind('u_news_show');
$u_news->match('/add', 'controller\\news::u_add')
	->bind('u_news_add');
$u_news->match('/{news}/edit', 'controller\\news::u_edit')
	->convert('news', 'service\\xdb::get')
	->bind('u_news_edit');

$u_news->assert('news', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$u_acc->mount('/news', $u_news);

// a
$a_news = $app['controllers_factory'];

$a_news->get('/', 'controller\\news::a_index')
	->bind('a_news_index');
$a_news->get('/{news}', 'controller\\news::a_show')
	->convert('news', 'service\\xdb::get')
	->bind('a_news_show');
$a_news->match('/add', 'controller\\news::a_add')
	->bind('a_news_add');
$a_news->match('/{news}/edit', 'controller\\news::a_edit')
	->convert('news', 'service\\xdb::get')
	->bind('a_news_edit');
$a_news->post('/{news}/approve', 'controller\\news::a_approve')
	->convert('news', 'service\\xdb::get')
	->bind('a_news_approve');

$a_news->assert('news', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$a_acc->mount('/news', $a_news);

/*
 *  docs
 */
// g

$g_doc = $app['controllers_factory'];

$g_doc->get('/', 'controller\\doc::g_index')
	->bind('g_doc_index');
$g_doc->get('/{doc}', 'controller\\doc::g_show')
	->convert('doc', 'service\\xdb::get')
	->bind('g_doc_show');

$g_doc->assert('doc', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

$g_acc->mount('/docs', $g_doc);

// i

$i_doc = $app['controllers_factory'];

$i_doc->get('/', 'controller\\doc::i_index')
	->bind('i_doc_index');
$i_doc->get('/{doc}', 'controller\\doc::i_show')
	->convert('doc', 'service\\xdb::get')
	->bind('i_doc_show');

$i_doc->assert('doc', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

$i_acc->mount('/docs', $i_doc);

// u

$u_doc = $app['controllers_factory'];

$u_doc->get('/', 'controller\\doc::u_index')
	->bind('u_doc_index');
$u_doc->get('/{doc}', 'controller\\doc::u_show')
	->convert('doc', 'service\\xdb::get')
	->bind('u_doc_show');
$u_doc->match('/add', 'controller\\doc::u_add')
	->bind('u_doc_add');
$u_doc->match('/{doc}/edit', 'controller\\doc::u_edit')
	->convert('doc', 'service\\xdb::get')
	->bind('u_doc_edit');

$u_doc->assert('doc', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

$u_acc->mount('/docs', $u_doc);

// a

$a_doc = $app['controllers_factory'];

$a_doc->get('/', 'controller\\doc::a_index')
	->bind('a_doc_index');
$a_doc->get('/{doc}', 'controller\\doc::a_show')
	->convert('doc', 'service\\xdb::get')
	->bind('a_doc_show');
$a_doc->match('/add', 'controller\\doc::a_add')
	->bind('a_doc_add');
$a_doc->match('/{doc}/edit', 'controller\\doc::a_edit')
	->convert('doc', 'service\\xdb::get')
	->bind('a_doc_edit');

$a_doc->assert('doc', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

$a_acc->mount('/docs', $a_doc);

/*
 *  forum
 */
//g

$g_forum = $app['controllers_factory'];

$g_forum->get('/', 'controller\\forum::g_index')
	->bind('g_forum_index');
$g_forum->get('/{forum}', 'controller\\forum::g_show')
	->convert('forum', 'service\\xdb::get')
	->bind('g_forum_show');

$g_forum->assert('forum', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$g_acc->mount('/forum', $g_forum);

//i

$i_forum = $app['controllers_factory'];

$i_forum->get('/', 'controller\\forum::i_index')
	->bind('i_forum_index');
$i_forum->get('/{forum}', 'controller\\forum::i_show')
	->convert('forum', 'service\\xdb::get')
	->bind('i_forum_show');

$i_forum->assert('forum', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$i_acc->mount('/forum', $i_forum);

//u

$u_forum = $app['controllers_factory'];

$u_forum->get('/', 'controller\\forum::u_index')
	->bind('u_forum_index');
$u_forum->get('/{forum}', 'controller\\forum::u_show')
	->convert('forum', 'service\\xdb::get')
	->bind('u_forum_show');
$u_forum->match('/add', 'controller\\forum::u_add')
	->bind('u_forum_add');
$u_forum->match('/{forum}/edit', 'controller\\forum::u_edit')
	->convert('forum', 'service\\xdb::get')
	->bind('u_forum_edit');

$u_forum->assert('forum', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$u_acc->mount('/forum', $u_forum);

//a

$a_forum = $app['controllers_factory'];

$a_forum->get('/', 'controller\\forum::a_index')
	->bind('a_forum_index');
$a_forum->get('/{forum}', 'controller\\forum::a_show')
	->convert('forum', 'service\\xdb::get')
	->bind('a_forum_show');
$a_forum->match('/add', 'controller\\forum::a_add')
	->bind('a_forum_add');
$a_forum->match('/{forum}/edit', 'controller\\forum::a_edit')
	->convert('forum', 'service\\xdb::get')
	->bind('a_forum_edit');

$a_forum->assert('forum', '^[a-z0-9][a-z0-9-]{6}[a-z0-9]$');

$a_acc->mount('/forum', $a_forum);

$cc->mount('/g', $g_acc);
$cc->mount('/i', $i_acc);
$cc->mount('/u', $u_acc);
$cc->mount('/a', $a_acc);

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
