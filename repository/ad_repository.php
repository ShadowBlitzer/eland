<?php

namespace repository;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ad_repository
{
	private $db;

	public function __construct(db $db)
	{
		$this->db = $db;
	}

	public function get_all(string $schema):array
	{

	}

	public function get(int $id, string $schema):array
	{
		$data = $this->db->fetchAssoc('select * 
			from ' . $schema . '.messages 
			where id = ?', [$id]);
	
		if (!$data)
		{
			throw new NotFoundHttpException(sprintf('Ad %d does not exist in %s', 
				$id, __CLASS__));
        }
		
		return $data;
	}
}
