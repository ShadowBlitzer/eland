<?php

namespace App\Twig;

use App\Service\SessionView;

class ViewExtension
{
	private $view;

	public function __construct(SessionView $sessionView)
	{
		$this->sessionView = $sessionView;
	}

	public function get(array $param, string $entity = null):array
	{
		return $this->sessionView->merge($param, $entity);
	}
}
