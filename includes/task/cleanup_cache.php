<?php

namespace eland\task;

use eland\cache;

class cleanup_cache
{
	protected $cache;

	public function __construct(cache $cache)
	{
		$this->cache = $cache;
	}

	function run($schema)
	{
		$this->cache->cleanup();
	}
}