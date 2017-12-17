<?php

namespace App\Twig;

use App\Service\DateFormatCache;

class DateFormatExtension
{
	private $dateFormatCache;
	private $format = [];

	public function __construct(
		DateFormatCache $dateFormatCache
	)
	{
		$this->dateFormatCache = $dateFormatCache;	
	}

	public function get(array $context, string $ts, string $precision):string
	{
		$time = strtotime($ts . ' UTC');

		if (!isset($this->format[$precision]))
		{
			$this->format[$precision] = $this->dateFormatCache
				->get($precision, $context['locale'], $context['schema']);
		}

		return strftime($this->format[$precision], $time);
	}

	public function getFormat(array $context, string $precision):string 
	{
		if (!isset($this->format[$precision]))
		{
			$this->format[$precision] = $this->dateFormatCache
				->get($precision, $context['locale'], $context['schema']);
		}

		return $this->format[$precision];
	}
}
