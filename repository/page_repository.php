<?php

namespace repository;

use service\xdb;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class page_repository
{
	private $xdb;

	public function __construct(xdb $xdb)
	{
		$this->xdb = $xdb;
	}

	public function get(string $id, string $schema):array
	{
		$data = $this->xdb->get('page', $id, $schema);
		
		if (!$data)
		{
			throw new NotFoundHttpException(sprintf('Page %s in schema %s does not exist in %s', 
				$id, $schema, __CLASS__));
		}

		return $data;
	}
}
