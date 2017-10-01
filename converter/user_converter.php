<?php

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\user_repository;

class user_converter
{
	private $repo;

	public function __construct(user_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(int $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}
