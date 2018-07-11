<?php declare(strict_types=1);

namespace converter;

use Symfony\Component\HttpFoundation\Request;
use repository\type_contact_repository;

class type_contact_converter
{
	private $repo;

	public function __construct(type_contact_repository $repo)
	{
		$this->repo = $repo;
	}

	public function get(int $id, Request $request)
	{
		return $this->repo->get($id, $request->attributes->get('schema'));
	}
}
