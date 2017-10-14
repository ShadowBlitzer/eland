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

	public function set(array $data, int $expires = 14400):string  // 4 hours
	{
		$token = $this->token->gen();
		$this->cache->set($prefix . $token, $data, $expires);

		return $token;
	}

	public function get(string $token):array
	{
		return $this->cache->get($prefix . $token);
	}
}

