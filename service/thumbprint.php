<?php

namespace service;

use Predis\Client as Redis;

class thumbprint
{
	private $redis;
	private $version;
	private $ttl = 604800; // 1 week
	private $redis_prefix = 'thumbprint_';

	public function __construct(Redis $redis, string $version)
	{
		$this->redis = $redis;
		$this->version = $version;
	}

	public function set_ttl(int $ttl)
	{
		$this->ttl = $ttl;
	}

	public function get(string $key)
	{
		$thumbprint = $this->redis->get($this->redis_prefix . $key);

		if (!$thumbprint)
		{
			return $this->version . 'renew-' . crc32(microtime());
		}

		return $this->version . $thumbprint;
	}

	public function set(string $key, string $content)
	{
		$redis_key = $this->redis_prefix . $key;
		$thumbprint = (string) crc32($content);

		$log = $thumbprint === $this->redis->get($redis_key) ? '' : '(new) ';

		error_log('set ' . $log .  'thumbprint for ' . 
			$key .' : ' . $thumbprint);

		$this->redis->set($redis_key, $thumbprint);	
		$this->redis->expire($redis_key, $this->ttl);		
	}

	public function del(string $key)
	{
		$this->redis->del($this->redis_prefix . $key);
	}
}
