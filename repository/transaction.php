<?php

namespace repository;

use Doctrine\DBAL\Connection as db;
use service\pagination;

class transaction
{
	private $db;

	public function __construct(db $db)
	{
		$this->db = $db;
	}

	public function get_all(pagination $pagination, string $schema):array
	{

	}

	public function get(int $id, string $schema):array
	{
		$transaction = $this->db->fetchAssoc('select *
			from ' . $schema . '.transactions
			where id = ?', [$id]);

		return $transaction;
	}
}
