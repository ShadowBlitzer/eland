<?php

namespace App\Twig;

use service\view as service_view;

class ViewExtension
{
	private $view;

	public function __construct(service_view $view)
	{
		$this->view = $view;
	}

	public function get(array $param, string $entity = null):array
	{
		return $this->view->merge($param, $entity);
	}
}
