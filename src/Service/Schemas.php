<?php

namespace App\Service;

use Doctrine\DBAL\Connection as db;

class Schemas
{
	private $db;
	private $schemas = [];

	public function __construct(db $db)
	{
		$this->db = $db;

		$schemas_db = $this->db->fetchAll('select schema_name from information_schema.schemata') ?: [];
		$schemas_db = array_map(function($row){ return $row['schema_name']; }, $schemas_db);
		$this->schemas = array_fill_keys($schemas_db, true);
	}

	public function get()
	{
		return $this->schemas;
	}

	public function is_set($test_schema)
	{
		return $this->schemas[$test_schema] ?? false;
	}
}
