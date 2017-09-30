<?php

namespace converter;

use Doctrine\DBAL\Connection as db;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class category_converter
{
	private $db;
	private $schema;

	public function __construct(db $db, string $schema)
	{
		$this->db = $db;
		$this->schema = $schema;
	}

	public function convert(int $id)
	{
		$category = $this->db->fetchAssoc('select * 
			from ' . $schema . '.categories 
			where id = ?', [$id]);
	
		if (null === $category)) 
		{
			throw new NotFoundHttpException(sprintf('Category %d does not exist in class %s', 
				$id, __CLASS__));
        }
		
		return $category;
	}
}
