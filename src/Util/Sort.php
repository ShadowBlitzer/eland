<?php declare(strict_types=1);

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

class Sort
{
	private $columns = [];
	private $defaultColumn;
	private $params;
	private $col;
	private $order;

	public function __construct(Request $request)
	{
		$this->params = $request->query->get('s') ?? [];
	}

	public function addColumns(array $columns):Sort
	{
		$this->columns = array_merge($this->columns, $columns);
		return $this;
	}

	public function addColumn(string $columnName, string $defaultOrder):Sort
	{
		$this->columns[$columnName] = $defaultOrder;
		return $this;
	}

	public function setDefault(string $column):Sort
	{
		$this->defaultColumn = $column;
		return $this;
	}

	public function query():string
	{
		$this->col = $this->params['col'] ?? $this->defaultColumn;

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
