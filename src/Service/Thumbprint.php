<?php

namespace App\Service;

use Predis\Client as Predis;

class Thumbprint
{
	private $redis;
	private $version;
	private $ttl = 604800; // 1 week
	private $prefix = 'thumbprint_';

	public function __construct(Predis $redis, string $version)
	{
		$this->redis = $redis;
		$this->version = $version;
	}

	public function set_ttl(int $ttl)
	{
		$this->ttl = $ttl;
	}

	public function get(string $key):string
	{
		$thumbprint = $this->redis->get($this->prefix . $key);

		if (!$thumbprint)
		{
			return $this->version . 'renew-' . crc32(microtime());
		}

		return $this->version . $thumbprint;
	}

	public function set(string $key, string $content)
	{
		$redis_key = $this->prefix . $key;
		$thumbprint = (string) crc32($content);

		$log = $thumbprint === $this->redis->get($redis_key) ? '' : '(new) ';

		error_log('set ' . $log .  'thumbprint for ' . 
			$key .' : ' . $thumbprint);

		$this->redis->set($redis_key, $thumbprint);	
		$this->redis->expire($redis_key, $this->ttl);		
	}

	public function del(string $key)
	{
		$this->redis->del($this->prefix . $key);
	}
}
