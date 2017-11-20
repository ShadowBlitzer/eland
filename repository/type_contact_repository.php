<?php

namespace repository;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class type_contact_repository
{
	private $db;

	public function __construct(db $db)
	{
		$this->db = $db;
	}

	public function get_all(string $schema)
	{

	}

	public function get_all_abbrev(string $schema):array
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
