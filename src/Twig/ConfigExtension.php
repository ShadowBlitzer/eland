<?php

namespace App\Twig;

use App\Service\Config;

class ConfigExtension
{
	private $config;

	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	public function get(array $context, string $key, string $schema = null):string
	{
		if (!isset($schema))
		{
			$schema = $context['schema'];
		}

		return $this->config->get($key, $schema);
	}
}
