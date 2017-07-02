<?php

namespace twig;

class config
{
	private $config;

	public function __construct(\service\config $config)
	{
		$this->config = $config;
	}

	public function get(string $key, string $schema)
	{
		if (!$schema)
		{
			return 'CONFIG.NO_SCHEMA';
		}

		if (!$key)
		{
			return 'CONFIG.NO_KEY';
		}

		return $this->config->get($key, $schema);
	}
}
