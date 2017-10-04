<?php

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\contact_repository;

class contact_converter
{
	private $repo;

	public function __construct(contact_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(int $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}
