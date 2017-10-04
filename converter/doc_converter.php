<?php

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\doc_repository;

class doc_converter
{
	private $repo;

	public function __construct(doc_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(string $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}
