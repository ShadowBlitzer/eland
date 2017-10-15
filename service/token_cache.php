<?php

namespace service;

use service\token;
use service\cache;

class token_cache
{
	private $token;
	private $cache;
	private $prefix = 'token_';

	public function __construct(token $token, cache $cache)
	{
		$this->token = $token;
		$this->cache = $cache;
	}

	public function gen(array $data, int $expires = 14400):string  // 4 hours
	{
		$token = $this->token->gen();
		$this->cache->set($this->prefix . $token, $data, $expires);

		return $token;
	}

	public function get(string $token):array
	{
		$key = $this->prefix . $token;
		$data = $this->cache->get($key);
		$this->cache->del($key);
		return $data;
	}
}

