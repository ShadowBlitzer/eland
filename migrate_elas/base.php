<?php

namespace migrate_elas;

use Doctrine\DBAL\Connection as db;
use service\xdb;
use service\ev;

abstract class base
{
	private $ev;
	private $db;
	private $xdb;
	private $schema;

	public function __construct(db $db, xdb $xdb, ev $ev)
	{
		$this->db = $db;
		$this->xdb = $xdb;
		$this->ev = $ev;
	}

	public function set_schema(string $schema)
	{
		$this->schema = $schema;
	}

	public function execute()
	{
		if (!isset($this->schema))
		{
			throw new Exception('Schema is not set');
		}
	}
}
