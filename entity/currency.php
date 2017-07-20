<?php

namespace entity;

use service\xdb;
use service\cache;
use Doctrine\DBAL\Connection as db;

class currency
{
	private $db;
	private $xdb;
	private $cache;

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
