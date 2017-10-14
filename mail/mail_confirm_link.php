<?php

namespace service;

use service\token_cache;
use Symfony\Component\HttpFoundation\RequestStack;

class mail_confirm_link
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

	public function set_data(array $data):mail_confirm_link
	{
		$this->data = $data;
		return $this;
	} 

	public function set_link_route(string $route):mail_confirm_link
	{
		$this->route = $route;
		return $this;
	}

	public function set_template(string $template):mail_confirm_link
	{
		$this->template = $template;
		return $this;
	}

	public function queue()
	{
		$request = $this->requestStack->getCurrentRequest();


		return;
	}



	public function get(string $route, array $data):string
	{

		$params = [
			'_locale' => $request->getLocale()
		];

		

		return $path;
	}
}
