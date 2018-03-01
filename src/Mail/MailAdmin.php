<?php

namespace App\Mail;

use App\Service\Config;

class MailAdmin
{
	private $config;

	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	public function get(string $schema):array
	{
		return explode(',', $this->config->get('admin', $schema));
	}
}
