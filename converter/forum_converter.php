<?php declare(strict_types=1);

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\forum_repository;

class forum_converter
{
	private $repo;

	public function __construct(forum_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(string $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}
