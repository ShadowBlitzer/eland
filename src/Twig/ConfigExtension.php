<?php

namespace App\Twig;

class ConfigExtension
{
	private $config;

	public function __construct(\service\config $config)
	{
		$this->config = $config;
	}

	public function get(string $key, string $schema)
	{
		return $this->config->get($key, $schema);
	}
}
