<?php

namespace App\Repository;

use Doctrine\DBAL\Connection as Db;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryRepository
{
	private $db;

	public function __construct(Db $db)
	{
		$this->db = $db;
	}

	public function get_all(string $schema):array
	{
		$categories = $this->db->fetchAll('select * 
			from ' . $schema . '.categories 
			order by fullname');
	
		$child_count_ary = [];
		
		foreach ($categories as $cat)
		{
			if (!isset($child_count_ary[$cat['id_parent']]))
			{
				$child_count_ary[$cat['id_parent']] = 0;
			}
		
			$child_count_ary[$cat['id_parent']]++;
		}

		foreach ($categories as &$cat)
		{
			if (isset($child_count_ary[$cat['id']]))
			{
				$cat['child_count'] = $child_count_ary[$cat['id']];
			}
		}

		return $categories;
	}

	public function get(int $id, string $schema):array
	{
		$data = $this->db->fetchAssoc('select * 
			from ' . $schema . '.categories 
			where id = ?', [$id]);
	
		if (!$data)
		{
			throw new NotFoundHttpException(sprintf('Category %d does not exist in %s', 
				$id, __CLASS__));
        }
		
		return $data;
	}
}
