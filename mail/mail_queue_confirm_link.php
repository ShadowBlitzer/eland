<?php

namespace mail;

use Symfony\Component\HttpFoundation\RequestStack;
use service\token_url;
use mail\mail_queue;

class mail_queue_confirm_link
{
	private $request;
	private $token_url;	
	private $mail_queue;

	private $data;
	private $template;
	private $route;
	private $to;

	public function __construct(	
		RequestStack $requestStack,
		token_url $token_url,
		mail_queue $mail_queue
	)
	{
		$this->request = $requestStack->getCurrentRequest();
		$this->token_url = $token_url;
		$this->mail_queue = $mail_queue;
	}

	public function set_to(array $to):mail_queue_confirm_link
	{
		$this->to = $to;
		return $this;
	}

	public function set_data(array $data):mail_queue_confirm_link
	{
		$this->data = $data;
		return $this;
	}

	public function set_template(string $template):mail_queue_confirm_link
	{
		$this->template = $template;
		return $this;
	}

	public function set_route(string $route):mail_queue_confirm_link
	{
		$this->route = $route;
		return $this;
	}

	public function put()
	{
		$this->put_param($this->to, $this->data, $this->template, $this->route);
		unset($this->to, $this->data, $this->template, $this->route);
		return;
	}

	public function put_param(array $to, array $data, string $template, string $route)
	{
		$confirm_link = $this->token_url->gen($route, $data);

		$vars = array_merge($data, [
			'confirm_link'		=> $confirm_link,
		]);
	
		$schema = $this->request->attributes->get('schema');

		if (isset($schema))
		{
			$this->mail_queue->set_schema($schema);
		}

		$this->mail_queue->set_template($template)
			->set_vars($vars)
			->set_to($to)
			->set_priority(1000000)
			->put();
	
		return;
	}
}
