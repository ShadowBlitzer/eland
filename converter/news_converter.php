<?php

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\news_repository;

class news_converter
{
	private $repo;

	public function __construct(news_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(int $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}