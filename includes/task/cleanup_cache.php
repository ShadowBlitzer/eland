<?php

namespace eland\task;

use eland\cache;
use eland\base_task;

class cleanup_cache extends base_task
{
	protected $cache;

	public function __construct(cache $cache)
	{
		$this->cache = $cache;
	}

	function run()
	{
		$this->cache->cleanup();
	}

	function has_schema()
	{
		return false;
	}

	function can_run()
	{
		return true;
	}

	function should_run()
	{

	}

	function get_interval()
	{
		return 7200;
	}
}
