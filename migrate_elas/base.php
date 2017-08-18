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

	public function set_section(string $section)
	{
		
	}

	public function execute()
	{
		if (!isset($this->schema))
		{
			throw new Exception('Schema is not set');
		}

		if (!$this->db->fetchColumn('select schema_name 
			from information_schema.schemata
			where schema_name = ?', [$this->schema]))
		{
			throw new Exception('eLAS schema ' . $this->schema . ' not found.');
		}
	}
}
