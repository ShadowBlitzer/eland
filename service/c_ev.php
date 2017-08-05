<?php

namespace service;

use service\ev;

class c_ev extends ev
{
	private $ev;

	public function __construct(ev $ev)
	{
		$this->ev = $ev;
	}

	public function set(string $id, array $data = [], array $meta = [])
	{

	}

	public function del(string $id, array $meta = [])
	{

	}

	public function get(string $id): array
	{

	}

	public function get_many(array $filters = [], string $query_extra = '')
	{

	}

	public function count_by_type_and_segment(string $type, string $segment = ''): int
	{

	}

	public function get_by_data(array $data)
	{

	}
}
