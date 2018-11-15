<?php

namespace App\Legacy\task;

use App\Legacy\service\cache;
use App\Legacy\model\task;
use App\Legacy\service\schedule;

class cleanup_cache extends task
{
	private $cache;

	public function __construct(cache $cache, schedule $schedule)
	{
		parent::__construct($schedule);
		$this->cache = $cache;
	}

	function process()
	{
		$this->cache->cleanup();
	}

	function get_interval()
	{
		return 7200;
	}
}
