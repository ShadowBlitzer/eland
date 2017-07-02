<?php

namespace controller_provider;

use util\app;
use Silex\Api\ControllerProviderInterface;

class u_user implements ControllerProviderInterface
{
	public function connect(app $app)
	{
		$c = $app['controllers_factory'];



		return $c;
	}
}