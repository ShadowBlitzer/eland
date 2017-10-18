<?php

namespace mail;

use service\config;

class mail_admin
{
	private $config;

	public function __construct(config $config)
	{
		$this->config = $config;
	}

	public function get(string $schema):array
	{
		return explode(',', $this->config->get('admin', $schema));
	}
}
