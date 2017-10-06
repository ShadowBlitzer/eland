<?php

namespace twig;

use service\config;
use Symfony\Component\Translation\TranslatorInterface;

class web_date
{
	private $schema;
	private $config;
	private $translator;

	private $format;
	private $format_ary = [];

	public function __construct(
		config $config, 
		TranslatorInterface $translator,
		string $schema
	)
	{
		$this->config = $config;
		$this->translator = $translator;
		$this->schema = $schema;
	}

	public function get(array $context, string $ts, string $precision = 'min'):string
	{
		$time = strtotime($ts . ' UTC');

		if (!isset($this->format))
		{
			$this->format = $this->config->get('date_format', $this->schema);

			if (strpos($this->format, '%') !== false)
			{
				$this->format = 'month_abbrev';
				$this->config->set('date_format', $this->format, $this->schema);
			}
		}

		if (!isset($this->format_ary[$precision]))
		{
			$this->format_ary[$precision] = $this->translator
				->trans('date_format.' . $this->format . '.' . $precision);
		}

		return strftime($this->format_ary[$precision], $time);
	}
}
