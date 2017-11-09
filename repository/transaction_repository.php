<?php

namespace repository;

use Doctrine\DBAL\Connection as db;
use service\pagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class transaction_repository
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
		$data = $this->db->fetchAssoc('select *
			from ' . $schema . '.transactions
			where id = ?', [$id]);

		if (!$data)
		{
			throw new NotFoundHttpException(sprintf('Transaction %d does not exist in %s', 
				$id, __CLASS__));
		}

		return $data;
	}

	public function get_next(int $id, string $schema)
	{
		return $this->db->fetchColumn('select id 
			from ' . $schema . '.transactions 
			where id > ? 
			order by id asc 
			limit 1', [$id]) ?? null;
	}

	public function get_prev(int $id, string $schema)
	{
		return $this->db->fetchColumn('select id
			from ' . $schema . '.transactions
			where id < ?
			order by id desc
			limit 1', [$id]) ?? null;
	}

	public function update_description(int $id, string $description, string $schema)
	{
		$this->db->update($schema . '.transactions', ['description'	=> $description], ['id' => $id]);
	}
}
