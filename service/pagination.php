<?php

namespace service;

class pagination
{
	private $start;
	private $limit;
	private $page = 0;
	private $table;

	private $adjacent_num = 2;
	private $row_count = 0;
	private $page_num = 0;
	private $entity = '';
	private $params = [];
	private $inline = false;
	private $out;

	private $limit_options = [
		10 		=> 10,
		25 		=> 25,
		50 		=> 50,
		100 	=> 100,
		250		=> 250,
		500		=> 500,
		1000 	=> 1000,
	];

	public function __construct()
	{
	}

	public function init($entity = '', $row_count = 0, $params = [], $inline = false)
	{
		$this->out = '';
		$this->limit = $params['limit'] ?: 25;
		$this->start = $params['start'] ?: 0;
		$this->row_count = $row_count;
		$this->entity = $entity;
		$this->params = $params;
		$this->inline = $inline;

		$this->page_num = ceil($this->row_count / $this->limit);
		$this->page = floor($this->start / $this->limit);

		if (!isset($this->limit_options[$this->limit]))
		{
			$this->limit_options[$this->limit] = $this->limit;
			ksort($this->limit_options);
		}
	}

	public function render()
	{
		if ($this->out)
		{
			echo $this->out;
			return;
		}

		$this->out .= '<div class="row print-hide"><div class="col-md-12">';
		$this->out .= '<ul class="pagination">';

/*
		if ($this->page)
		{
			$this->out .= $this->get_link($this->page - 1, '&#9668;');
		}
*/

		$min_adjacent = $this->page - $this->adjacent_num;
		$max_adjacent = $this->page + $this->adjacent_num;

		$min_adjacent = $min_adjacent < 0 ? 0 : $min_adjacent;
		$max_adjacent = $max_adjacent > ($this->page_num - 1) ? $this->page_num - 1 : $max_adjacent;

		if ($min_adjacent)
		{
			$this->out .= $this->get_link(0);
		}

		for($page = $min_adjacent; $page < $max_adjacent + 1; $page++)
		{
			$this->out .= $this->get_link($page);
		}

		if ($max_adjacent != $this->page_num - 1)
		{
			$this->out .= $this->get_link($this->page_num - 1);
		}

/*
		if ($this->page < $this->page_num - 1)
		{
			$this->out .= $this->get_link($this->page + 1, '&#9658;');
		}
*/

		$this->out .= '</ul>';

		$this->out .= '<div class="pull-right hidden-xs">';
		$this->out .= '<div>';
		$this->out .= 'Totaal ';
		$this->out .= $this->row_count;
		$this->out .= ', Pagina ';
		$this->out .= $this->page + 1;
		$this->out .= ' van ';
		$this->out .= $this->page_num;
		$this->out .= '</div>';

		if (!$this->inline)
		{
			$this->out .= '<div>';
			$this->out .= '<form action="' . $this->entity . '.php">';

			$this->out .= 'Per pagina: ';
			$this->out .= '<select name="limit" onchange="this.form.submit();">';
			$this->out .= get_select_options($this->limit_options, $this->limit);
			$this->out .= '</select>';

			$action_params = $this->params;
			unset($action_params['limit']);
			$action_params['start'] = 0;
			$action_params = array_merge($action_params,  get_session_query_param());

			$action_params = http_build_query($action_params, 'prefix', '&');
			$action_params = urldecode($action_params);
			$action_params = explode('&', $action_params);

			foreach ($action_params as $param)
			{
				[$name, $value] = explode('=', $param);
				$this->out .= '<input name="' . $name . '" value="' . $value . '" type="hidden">';
			}

			$this->out .= '</form>';
			$this->out .= '</div>';
		}
		$this->out .= '</div>';
		$this->out .= '</div></div>';

		echo $this->out;
	}

	public function get_link($page, $text = '')
	{
		$params = $this->params;
		$params['start'] = $page * $this->limit;
		$params['limit'] = $this->limit;

		$pag_link = '<li';
		$pag_link .= $page == $this->page ? ' class="active"' : '';
		$pag_link .= '>';
		$pag_link .= '<a href="';
		$pag_link .= generate_url($this->entity, $params);
		$pag_link .= '">';
		$pag_link .= $text == '' ? $page + 1 : $text;
		$pag_link .= '</a></li>';

		return $pag_link;
	}
}
