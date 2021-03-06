<?php

namespace service;

use service\config;

class template_vars
{
	protected $config;

	public function __construct(config $config)
	{
		$this->config = $config;
	}

	public function get(string $schema):array
	{
		$return = [
			'tag'				=> $this->config->get('systemtag', $schema),
			'name'				=> $this->config->get('systemname', $schema),
			'currency'			=> $this->config->get('currency', $schema),
			'currencyratio'		=> $this->config->get('currencyratio', $schema),
			'admin'				=> $this->config->get('admin', $schema),
			'msgexpcleanupdays'	=> $this->config->get('msgexpcleanupdays', $schema),
		];

		return $return;
	}
}
