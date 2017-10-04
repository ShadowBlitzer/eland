<?php

namespace repository;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class contact_repository
{
	private $db;

	public function __construct(db $db)
	{
		$this->db = $db;
	}

	public function get_all(string $schema)
	{

	}

	public function get(int $id, string $schema):array
	{
		$data = $this->db->fetchAssoc('select *
			from ' . $schema . '.contact 
			where id = ?', [$id]);
	
		if (!$data)
		{
			throw new NotFoundHttpException(sprintf(
				'Contact type %d does not exist in %s', 
				$id, __CLASS__));
        }
		
		return $data;
	}
}
