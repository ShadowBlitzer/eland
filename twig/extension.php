<?php

namespace twig;

class extension extends \Twig_Extension
{
	public function getFilters()
	{
		return [
			new \Twig_Filter('underline', [$this, 'underline']),
			new \Twig_Filter('date_format', 'twig\\date_format::get', [
				'needs_context'		=> true,
			]),
		];
	}

	public function getFunctions()
	{
		return [
			new \Twig_Function('distance_p', 'twig\\distance::format_p'),
			new \Twig_Function('datepicker_format', 'twig\\date_format::datepicker_format', [
				'needs_context'		=> true,
			]),
			new \Twig_Function('datepicker_placeholder', 'twig\\date_format::datepicker_placeholder', [
				'needs_context'		=> true,
			]),
			new \Twig_Function('config', 'twig\\config::get'),
		];
	}

	public function underline(string $input, string $char = '-')
	{
		$len = strlen($input);
		return $input . "\r\n" . str_repeat($char, $len);
	}
}
