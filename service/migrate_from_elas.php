<?php

namespace service;

use Doctrine\DBAL\Connection as db;
use service\xdb;
use service\cache;
use service\ev;

class migrate_from_elas
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

	public function clear(int $id, string $schema = '')
	{

	}

	public function get(int $id, string $schema = '')
	{

	}

	private function read_from_db(int $id, string $schema)
	{

	}
}
