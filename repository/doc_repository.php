<?php

namespace repository;

use service\xdb;
use service\pagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class doc_repository
{
	private $xdb;

	public function __construct(xdb $xdb)
	{
		$this->xdb = $xdb;
	}

	public function get_all(pagination $pagination, string $schema):array
	{

	}

	public function get(string $id, string $schema):array
	{
		$data = $this->xdb->get('docs', $id, $schema);

		if (!$data)
		{
			throw new NotFoundHttpException(sprintf('Document %s does not exist in %s', 
				$id, __CLASS__));
		}

		return $data;
	}
}
