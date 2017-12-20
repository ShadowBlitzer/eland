<?php

namespace App\Twig;

use App\Service\SessionView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ViewExtension extends AbstractExtension
{
	private $view;

	public function __construct(SessionView $sessionView)
	{
		$this->sessionView = $sessionView;
	}

	public function getFilters()
    {
		return [
			new TwigFilter('view', ['App\Twig\ViewExtension', 'get']),           
        ];
    }

	public function get(array $params, string $entity):array
	{
		return $this->sessionView->merge($params, $entity);
	}
}
