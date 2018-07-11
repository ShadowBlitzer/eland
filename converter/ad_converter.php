<?php declare(strict_types=1);

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\ad_repository;

class ad_converter
{
	private $repo;

	public function __construct(ad_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(int $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}
