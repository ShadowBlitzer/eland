<?php declare(strict_types=1);

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\page_repository;

class page_converter
{
	private $repo;

	public function __construct(page_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(string $page, Request $request)
	{
		return $this->repo->get($page, $request->attributes->get('schema'));
	}
}
