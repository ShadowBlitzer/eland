<?php

namespace App\Repository;

use Doctrine\DBAL\Connection as Db;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TypeContactRepository
{
	private $db;

	public function __construct(Db $db)
	{
		$this->db = $db;
	}

	public function getAll(string $schema)
	{

	}

	public function getAllAbbrev(string $schema):array
	{
		$ary = [];
	
		$rs = $this->db->prepare('select id, abbrev from ' . $schema . '.type_contact');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$ary[$row['id']] = $row['abbrev'];
		}

		return $ary;
	}

	public function get(int $id, string $schema):array
	{
		$data = $this->db->fetchAssoc('select *
			from ' . $schema . '.type_contact 
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
