<?php

namespace entity;

use service\xdb;
use service\cache;
use Doctrine\DBAL\Connection as db;

class custom_field
{
	private $db;
	private $xdb;
	private $cache;

	private $data = [
		'eid'				=> '',
		'uid'				=> '',
		'gid'				=> '',
		'name'				=> [
			'nl'	=> '',
			'en'	=> '',
			'fr'	=> '',
		],
		'type'		=> '',
		'required'	=> false,
	];

	public function __construct(db $db, xdb $xdb, cache $cache)
	{
		$this->db = $db;
		$this->xdb = $xdb;
		$this->cache = $cache;
	}

	public function sync_to_elas()
	{

	}

	public function sync_to_eland()
	{

	}
}
