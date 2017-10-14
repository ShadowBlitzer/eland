<?php

namespace service;

use service\cache;

class boot_count
{
	private $cache;
	private $boot;

	public function __construct(cache $cache)
	{
		$this->cache = $cache;

		$this->boot = $this->cache->get('boot');
	}

	public function get(string $key):int
	{
		if (!isset($this->boot[$key]))
		{
			$this->boot[$key] = 0;
		}

		$this->boot[$key]++;

		$this->cache->set('boot', $this->boot);

		return $this->boot[$key];
	}
}
