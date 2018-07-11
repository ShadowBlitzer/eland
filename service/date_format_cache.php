<?php declare(strict_types=1);

namespace service;

use Predis\Client as redis;
use Symfony\Component\Translation\TranslatorInterface;

class date_format_cache
{
	private $redis;
	private $config;
	private $translator;
	private $prefix = 'date_format_cache_';
	private $ttl = 604800;

	public function __construct(redis $redis, config $config, TranslatorInterface $translator)
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
				$trans_key = array_search($pre, $msgs);

				$pre = 'month_abbrev';

				if ($trans_key !== false)
				{
					list($tr_date_format, $date_format, $tr_precision) = explode('.', $trans_key);

					if ($tr_date_format === 'date_format' 
						&& $tr_precision === 'sec'
						&& $date_format)
					{
						$pre = $date_format;	
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
