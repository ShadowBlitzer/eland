<?php

namespace App\Repository;

use App\Service\Xdb;
use App\Service\Pagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocRepository
{
	private $xdb;

	public function __construct(Xdb $xdb)
	{
		$this->xdb = $xdb;
	}

	public function getAll(Pagination $pagination, string $schema):array
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
