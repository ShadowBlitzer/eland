<?php

namespace App\Service;

use Doctrine\DBAL\Connection as db;
use service\xdb;
use service\cache;
use service\ev;

class MigrateElas
{
	private $db;
	private $xdb;
	private $cache;
	private $ev;

	public function __construct(db $db, xdb $xdb, cache $cache, ev $ev)
	{
		$this->db = $db;
		$this->xdb = $xdb;
		$this->cache = $cache;
		$this->ev = $ev;
	}

	public function should_run()
	{

	}

	public function run()
	{

	}
}
