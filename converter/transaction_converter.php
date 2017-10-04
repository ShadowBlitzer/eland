<?php

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\transaction_repository;

class transaction_converter
{
	private $repo;

	public function __construct(transaction_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(int $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}
