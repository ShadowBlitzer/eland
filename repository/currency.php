<?php

namespace repository;

use service\ev;

class currency
{
	private $db;
	private $ev;

	private $data = [
		'uid'				=> '',
		'eid'				=> '',
		'steward'			=> '',
		'name'				=> '',
		'site_name'			=> '',
		'mail_tag'			=> '',
		'path_id'			=> '',
		'elas_schema'		=> '',
		'elas_subdomain'	=> '',
		'redirect'			=> '',
		'min_limit'			=> '',
		'max_limit'			=> '',
		'landing_page'		=> 'ad_index',
		'custom'		=> [
			'dddddzzfjeid' => ['account', 'datetime', 'info moment'],
			'sjkdksjlksqj' => ['datetime', 'lidgeld'],
			'klds-doeoopd' => ['int', ''],
		],
	];

	public function __construct(ev $ev)
	{
		$this->db = $db;
		$this->ev = $ev;
		$this->cache = $cache;
	}

	public function set(string $id, array $data)
	{

		$config = $this->db->fetchAssoc('select * from ' . $this->schema . '.config');

		foreach ($config as )
	}


}
