<?php

namespace util;

use Symfony\Component\HttpFoundation\Request;

class pagination
{
	private $start;
	private $limit;
	private $row_count = 0;

	private $adjacent_num = 1;

	private $limit_options = [
		10 		=> 10,
		25 		=> 25,
		50 		=> 50,
		100 	=> 100,
		250		=> 250,
		500		=> 500,
		1000 	=> 1000,
	];

	public function __construct(Request $request, int $row_count)
	{		
		$params = $request->query->get('p') ?? [];
		$this->limit = $params['limit'] ?? 25;
		$this->start = $params['start'] ?? 0;
		$this->row_count = $row_count;
	}

	public function query():string
	{
		return ' limit ' . $this->limit . ' offset ' . $this->start;
	}

	public function get():array
	{
		if (!isset($this->limit_options[$this->limit]))
		{
			$this->limit_options[$this->limit] = $this->limit;
			ksort($this->limit_options);
		}

		$page = floor($this->start / $this->limit) + 1;
		$page_count = ceil($this->row_count / $this->limit);
	
		$min = $page - $this->adjacent_num;
		$max = $page + $this->adjacent_num;

		$min = $min < 1 ? 1 : $min;
		$max = $max > $page_count ? $page_count : $max;

		return [
			'limit_options'		=> $this->limit_options,
			'limit'				=> $this->limit,
			'start'				=> $this->start,
			'row_count'			=> $this->row_count,
			'min'				=> $min,
			'max'				=> $max,
			'page'				=> $page,
			'page_count'		=> $page_count,
		];
	}

	public function set_limit_options(array $limit_options):pagination
	{
		$this->limit_options = [];
	
		foreach ($limit_options as $option)
		{
			$this->limit_options[$option] = $option;
		}

		return $this;
	}

	public function remove_limit_option(int $option):pagination
	{
		unset($this->limit_options[$option]);
		return $this;
	}

	public function add_limit_option(int $option):pagination
	{
		$this->limit_options[$option] = $option;
		return $this;
	}

	public function init($entity = '', $row_count = 0, $params = [], $inline = false)
	{
		$this->limit = $params['limit'] ?: 25;
		$this->start = $params['start'] ?: 0;
		$this->row_count = $row_count;
		$this->entity = $entity;
		$this->params = $params;
		$this->inline = $inline;

		$this->page_num = ceil($this->row_count / $this->limit);
		$this->page = floor($this->start / $this->limit);

		if (!$this->limit_options[$this->limit])
		{
			$this->limit_options[$this->limit] = $this->limit;
			ksort($this->limit_options);
		}
	}

	public function render()
	{

		echo '<div class="row print-hide"><div class="col-md-12">';
		echo '<ul class="pagination">';

		if ($this->page)
		{
			echo $this->add_link($this->page - 1, '&#9668;');
		}

		$min_adjacent = $this->page - $this->adjacent_num;
		$max_adjacent = $this->page + $this->adjacent_num;

		$min_adjacent = ($min_adjacent < 0) ? 0 : $min_adjacent;
		$max_adjacent = ($max_adjacent > $this->page_num - 1) ? $this->page_num - 1 : $max_adjacent;

		if ($min_adjacent)
		{
			echo $this->add_link(0);
		}

		for($page = $min_adjacent; $page < $max_adjacent + 1; $page++)
		{
			echo $this->add_link($page);
		}

		if ($max_adjacent != $this->page_num - 1)
		{
			echo $this->add_link($this->page_num - 1);
		}

		if ($this->page < $this->page_num - 1)
		{
			echo $this->add_link($this->page + 1, '&#9658;');
		}

		echo '</ul>';

		echo '<div class="pull-right hidden-xs">';
		echo '<div>';
		echo 'Totaal '.$this->row_count.', Pagina ' . ($this->page + 1).' van ' . $this->page_num;
		echo '</div>';

		if (!$this->inline)
		{
			echo '<div>';
			echo '<form action="' . $this->entity . '.php">';

			echo 'Per pagina: ';
			echo '<select name="limit" onchange="this.form.submit();">';
			render_select_options($this->limit_options, $this->limit);
			echo '</select>';

			$action_params = $this->params;
			unset($action_params['limit']);
			$action_params['start'] = 0;
			$action_params = array_merge($action_params,  get_session_query_param());

			foreach ($action_params as $name => $value)
			{
				if (isset($value))
				{
					echo '<input name="' . $name . '" value="' . $value . '" type="hidden">';
				}
			}

			echo '</form>';
			echo '</div>';
		}
		echo '</div>';

		echo '</div></div>';
	}

	public function add_link($page, $text = '')
	{
		$params = $this->params;
		$params['start'] = $page * $this->limit;
		$params['limit'] = $this->limit;

		$pag_link = '<li';
		$pag_link .= ($page == $this->page) ? ' class="active"' : '';
		$pag_link .= '>';
		$pag_link .= '<a href="' . generate_url($this->entity, $params) . '">';
		$pag_link .= ($text == '') ? ($page + 1) : $text;
		$pag_link .= '</a></li>';

		return $pag_link;
	}
}
