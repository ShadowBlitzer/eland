<?php

namespace twig;

use service\config as conf;

class config
{
	private $config;

	public function __construct(conf $config)
	{
		$this->config = $config;
	}

	public function get(\Twig_Environment $twig, $key)
	{
		$request = $twig->getGlobals()['app']['request_stack']->getCurrentRequest();
		$schema = $request->attributes->get('_route_params')['schema'];

		if (!$schema)
		{
			return 'CONFIG.NO_SCHEMA';
		}

		return $schema;

		if (!$key)
		{
			return 'CONFIG.NO_KEY';
		}

		return $this->config->get($key, $schema);
	}
}
