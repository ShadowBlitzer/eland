<?php

namespace twig;

class extension extends \Twig_Extension
{
	public function getFilters()
	{
		return [
/*			new \Twig_Filter('config_en', 'config_en::get_twig', [
				'needs_environment'		=> true,
			]),*/
		];
	}

	public function getFunctions()
	{
		return [
			new \Twig_Function('distance_p', 'twig\\distance::format_p'),
			new \Twig_Function('datepicker_format', 'twig\\date_format::datepicker_format'),
			new \Twig_Function('datepicker_placeholder', 'twig\\date_format::datepicker_placeholder'),
			new \Twig_Function('config', 'twig\\config::get', ['needs_environment' => true]),
		];
	}
}
