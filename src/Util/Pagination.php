<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

class Pagination
{
	private $start;
	private $limit;
	private $rowCount = 0;

	private $adjacentNum = 1;

	private $limitOptions = [
		10 		=> 10,
		25 		=> 25,
		50 		=> 50,
		100 	=> 100,
		250		=> 250,
		500		=> 500,
		1000 	=> 1000,
	];

	public function __construct(Request $request, int $rowCount)
	{		
		$params = $request->query->get('p') ?? [];
		$this->limit = $params['limit'] ?? 25;
		$this->start = $params['start'] ?? 0;
		$this->rowCount = $rowCount;
	}

	public function query():string
	{
		return ' limit ' . $this->limit . ' offset ' . $this->start;
	}

	public function get():array
	{
		if (!isset($this->limitOptions[$this->limit]))
		{
			$this->limitOptions[$this->limit] = $this->limit;
			ksort($this->limitOptions);
		}

		$page = floor($this->start / $this->limit) + 1;
		$pageCount = ceil($this->rowCount / $this->limit);
		$pageCount = $pageCount < 1 ? 1 : $pageCount;
	
		$min = $page - $this->adjacentNum;
		$max = $page + $this->adjacentNum;

		$min = $min < 1 ? 1 : $min;
		$max = $max > $pageCount ? $pageCount : $max;

		return [
			'limit_options'		=> $this->limitOptions,
			'limit'				=> $this->limit,
			'start'				=> $this->start,
			'row_count'			=> $this->rowCount,
			'min'				=> $min,
			'max'				=> $max,
			'page'				=> $page,
			'page_count'		=> $pageCount,
		];
	}
}
