<?php

namespace service;

class twig_extension extends \Twig_Extension
{
/*
	public function __construct()
	{
	}
*/
	public function getFilters()
	{
		return [
		];
	}

	public function getFunctions()
	{
		return [
			new \Twig_Function('distance_p', ['util\distance', 'format_p']),
			new \Twig_Function('datepicker_format', ['service\date_format', 'datepicker_format']),
			new \Twig_Function('datepicker_placeholder', ['service\date_format', 'datepicker_placeholder']),
		];
	}


}
