<?php

namespace controller_provider;

use util\app;
use Silex\Api\ControllerProviderInterface;

class user implements ControllerProviderInterface
{
	public function connect(app $app)
	{
		$c = $app['controllers_factory'];

		$c->get('/', function (Application $app) {
			return $app->redirect('/hello');
		});

		return $controllers;
	}
}