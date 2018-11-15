<?php declare(strict_types=1);

namespace App\Mail;

use App\Service\Config;

class MailNewsadmin
{
	private $config;

	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	public function get(string $schema):array
	{
		return explode(',', $this->config->get('newsadmin', $schema));
	}
}
