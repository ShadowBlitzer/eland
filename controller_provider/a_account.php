<?php

namespace controller_provider;

use util\app;
use Silex\Api\ControllerProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class a_account implements ControllerProviderInterface
{
	public function connect(app $app)
	{
		$c = $app['controllers_factory'];

		$c->assert('account_type', '^(\'active|new|leaving|interlets|pre-active|post-active|all\')$')
			->assert('account', '^[a-z0-9][a-z0-9-]{6,6}[a-z0-9]$')
			->convert('account', 'service\\cache::get');

		$c->match('/add', 'controller\\user::add')
			->bind('a_account_add');
		$c->match('/{account}/edit', 'controller\\user::edit')
			->bind('a_account_edit');
		$c->match('/{account}/del', 'controller\\user::del')
			->bind('a_account_del');

		$c->get('/{account_type}/{account}', 'controller\\account::show_typed')
			->bind('a_account_show_typed');
		$c->get('/{account}', 'controller\\account::show')
			->bind('a_account_show');
		$c->get('/{account_type}', 'controller::index_typed')
			->bind('a_account_index_typed');
		$c->get('/', 'controller\\account::index')
			->bind('a_account_index');

		return $c;
	}
}