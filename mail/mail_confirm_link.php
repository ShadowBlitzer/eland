<?php

namespace mail;

use service\queue;
use service\config;
use mail\mail_queue;
use service\token;
use service\cache;

class mail_confirm_link
{
	private $mail_queue;
	private $token;
	private $cache;
	private $prefix = 'token_';
	private $ttl = 120;


	public function __construct(queue $mail_queue, token $token, cache $cache)
	{
		$this->mail_queue = $mail_queue;
		$this->token = $token;
		$this->cache = $cache;
	}

	public function send(array $data, string $template)
	{
		$token = 
		$this->cache->set()



	}
}
