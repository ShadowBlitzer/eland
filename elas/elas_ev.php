<?php

namespace elas;

use service\ev;

class elas_ev
{
	private $ev;
	private $currency;

	public function __construct(ev $ev)
	{
		$this->ev = $ev;
	}

	public function set_currency(string $currency)
	{
		$this->currency = $currency;
		return $this;
	}

	public function set_schema(string $schema)
	{

	}

	public function set(string $id, array $data = [], array $meta = [])
	{
		$this->ev->set('');
	}

	public function del(string $id, array $meta = [])
	{

	}

	public function count(string $type): int
	{
		return $this->ev->count_by_type_and_segment($type, $this->currency);
	}

	public function count_by_data(array $data): int
	{

	}

	public function get_by_data(array $data)
	{

	}
}
