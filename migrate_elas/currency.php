<?php

namespace migrate_elas;

use service\ev;
use Doctrine\DBAL\Connection as db;

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

	public function __construct(db $db, ev $ev)
	{
		$this->db = $db;
		$this->ev = $ev;
		$this->cache = $cache;
	}

	public function process(string $id)
	{
		$data = [
			''
		];

		$meta = [
			'event_time'	=> 
			'event'			=> 'create_currency',
		]

		$config = $this->db->fetchAssoc('select * from ' . $this->schema . '.config');

		foreach ($config as )
	}

/*
$map = [
	'config' => [
		'entity' => 'currency'

	]
]
*/


	public function get_next(): string
	{

	}

	public function get_priority(): int
	{
		return 1000;
	}
}
