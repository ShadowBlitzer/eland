<?php

namespace service;

use service\token_cache;
use Symfony\Component\HttpFoundation\RequestStack;

class confirm_link
{
	private $mail_queue;
	private $token_cache;
	private $request_stack;
	private $ttl = 120;

	public function __construct(
		RequestStack $requestStack, 
		UrlGenerator $urlGenerator,	
		token_cache $token_cache
	)
	{
		$this->requestStack = $requestStack;
		$this->urlGenerator = $urlGenerator;
		$this->token_cache = $token_cache;
	}

	public function get(string $route, array $data):string
	{
		$request = $this->requestStack->getCurrentRequest();
		$params = [
			'_locale' => $request->getLocale()
		];

		

		return $path;
	}
}
