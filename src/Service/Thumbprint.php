<?php declare(strict_types=1);

namespace App\Service;

use Predis\Client as Redis;

class Thumbprint
{
	private $redis;
	private $version = '';
	private $ttl = 604800; // 1 week
	private $prefix = 'thumbprint_';

	public function __construct(Redis $redis)
	{
		$this->redis = $redis;
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
		$redisKey = $this->prefix . $key;
		$thumbprint = (string) crc32($content);

		$log = $thumbprint === $this->redis->get($redisKey) ? '' : '(new) ';

		error_log('set ' . $log .  'thumbprint for ' . 
			$key .' : ' . $thumbprint);

		$this->redis->set($redisKey, $thumbprint);	
		$this->redis->expire($redisKey, $this->ttl);		
	}

	public function del(string $key)
	{
		$this->redis->del($this->prefix . $key);
	}
}