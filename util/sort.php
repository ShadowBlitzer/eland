<?php

namespace util;

use Symfony\Component\HttpFoundation\Request;

class sort
{
	private $columns = [];
	private $default_column;
	private $params;
	private $col;
	private $order;

	public function __construct(Request $request)
	{
		$this->params = $request->query->get('s') ?? [];
	}

	public function add_columns(array $columns):sort
	{
		$this->columns = array_merge($this->columns, $columns);
		return $this;
	}

	public function add_column(string $column_name, string $default_order):sort
	{
		$this->columns[$column_name] = $default_order;
		return $this;
	}

	public function set_default(string $column):sort
	{
		$this->default_column = $column;
		return $this;
	}

	public function query():string
	{
		$this->col = $this->params['col'] ?? $this->default_column;

		if (!isset($this->col))
		{
			throw new logical_exception(sprintf('no default sort column set in %s', __CLASS__));
		}

		if (!isset($this->columns[$this->col]))
		{
			throw new logical_exception(sprintf('unsortable column %s selected in %s', $this->col, __CLASS__));
		}

		$this->order = $this->params['order'] ?? $this->columns[$this->col];

		if (!in_array($this->order, ['asc', 'desc']))
		{
			throw new logical_exception(sprintf('order %s has to be asc or desc in %s', $this->order, __CLASS__));
		}

		return ' order by ' . $this->col . ' ' . $this->order;
	}

	public function get():array
	{
		return [
			'columns' 	=> $this->columns,
			'col'	 	=> $this->col,
			'order'		=> $this->order,
		];
	}
}
