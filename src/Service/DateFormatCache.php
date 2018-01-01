<?php

namespace App\Service;

use Predis\Client as Predis;
use Symfony\Component\Translation\TranslatorInterface;
use App\Service\Config;

class DateFormatCache
{
	private $redis;
	private $config;
	private $translator;

	private $prefix = 'date_format_cache_';
	private $ttl = 604800;

	public function __construct(Predis $redis, Config $config, TranslatorInterface $translator)
	{
		$this->redis = $redis;
		$this->config = $config;
		$this->translator = $translator;
	}

	public function get(string $precision, string $locale, string $schema):string
	{
		$key = $this->prefix . $schema . '_' . $locale . '_' . $precision;
		$format = $this->redis->get($key);

		if (!$format)
		{
			$pre = $this->config->get('date_format', $schema);
			
			if (strpos($pre, '%') !== false)
			{
				$catalogue = $this->translator->getCatalogue();
				$msgs = $catalogue->all();
				$transKey = array_search($pre, $msgs);

				$pre = 'month_abbrev';

				if ($transKey !== false)
				{
					list($trDateFormat, $dateFormat, $trPrecision) = explode('.', $transKey);

					if ($trDateFormat === 'date_format' 
						&& $trPrecision === 'sec'
						&& $dateFormat)
					{
						$pre = $dateFormat;	
					}
				}

				$this->config->set('date_format', $pre, $schema);
			}

			$format = $this->translator->trans(
				'date_format.' . $pre . '.' . $precision, [], null, $locale);
	
			$this->redis->set($key, $format);
			$this->redis->expire($key, $this->ttl);			
		}

		return $format;
	}

	public function clear(string $schema)
	{
		$k1 = $this->prefix . $schema . '_';

		foreach ($this->translator->getFallbackLocales() as $locale)
		{
			$k2 = $k1 . $locale . '_';

			foreach (['day', 'min', 'sec'] as $precision)
			{
				$this->redis->del($k2 . $precision);
			}
		}
	}
}
