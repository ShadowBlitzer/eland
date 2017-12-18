<?php

namespace App\Repository;

use Doctrine\DBAL\Connection as db;
use App\Service\Pagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransactionRepository
{
	private $db;

	public function __construct(db $db)
	{
		$this->db = $db;
	}

	public function getAll(Pagination $pagination, string $schema):array
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

	public function getNext(int $id, string $schema)
	{
		return $this->db->fetchColumn('select id 
			from ' . $schema . '.transactions 
			where id > ? 
			order by id asc 
			limit 1', [$id]) ?? null;
	}

	public function getPrev(int $id, string $schema)
	{
		return $this->db->fetchColumn('select id
			from ' . $schema . '.transactions
			where id < ?
			order by id desc
			limit 1', [$id]) ?? null;
	}

	public function updateDescription(int $id, string $description, string $schema)
	{
		$this->db->update($schema . '.transactions', ['description'	=> $description], ['id' => $id]);
	}
}
