<?php

namespace twig;

use service\config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class web_date
{
	private $request;
	private $schema;
	private $config;
	private $translator;

	private $format;
	private $format_ary = [];

	/**
	 *
	 */

	public function __construct(
		config $config, 
//		RequestStack $requestStack, 
		TranslatorInterface $translator,
		string $schema
	)
	{
		$this->config = $config;
//		$this->request = $requestStack->getCurrentRequest();
//		$this->schema = $this->request->attributes->get('_route_params')['schema'];
		$this->schema = $schema;

		$this->format = $this->config->get('date_format', $this->schema);

		if (!$this->format)
		{
			$this->format = '%e %b %Y, %H:%M:%S';
		}

		$sec = $this->format;

		if (!isset(self::$formats[$sec]))
		{
			$sec = '%e %b %Y, %H:%M:%S';
		}

		$this->format_ary = self::$formats[$sec];
		$this->format_ary['sec'] = $sec;
	}

	/**
	 * to do: get schema for static method version
	 */

	public function get($ts = false, $precision = 'min')
	{
		$time = strtotime($ts . ' UTC');

		if (isset($this))
		{
			return strftime($this->format_ary[$precision], $time);
		}

		if (!isset($format_ary))
		{
			$format = $this->config->get('date_format', $this->schema);

			if (!$format)
			{
				$format = '%e %b %Y, %H:%M:%S';
			}

			$sec = $format;

			if (!isset(self::$formats[$sec]))
			{
				$sec = '%e %b %Y, %H:%M:%S';
			}

			$format_ary = self::$formats[$sec];
			$format_ary['sec'] = $sec;
		}

		return strftime($format_ary[$precision], $time);
	}
}
