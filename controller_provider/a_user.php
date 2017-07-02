<?php

namespace controller_provider;

use util\app;
use Silex\Api\ControllerProviderInterface;

class a_account implements ControllerProviderInterface
{
	public function connect(app $app)
	{
		$c = $app['controllers_factory'];

		$c->assert('account_type', '^(\'active|new|leaving|interlets|pre-active|post-active|all\')$')
			->assert('account', '\d+')
			->convert('user', 'service\\user_cache::get');

		$c->match('/add', 'controller\\user::add')->bind('a_user_add');
		$c->match('/{user}/edit', 'controller\\user::edit')->bind('a_user_edit');
		$c->match('/{user}/del', 'controller\\user::del')->bind('a_user_del');

		$c->get('/{user_type}/{user}', 'controller\\user::show_with_type')->bind('user_show_with_type');
		$c->get('/{user}', 'controller\\user::show')->bind('user_show');
		$c->get('/', 'controller\\user::index');

		return $c;
	}
}