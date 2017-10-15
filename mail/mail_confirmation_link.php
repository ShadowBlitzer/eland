<?php

namespace mail;

use Symfony\Component\HttpFoundation\RequestStack;
use service\token_url;
use mail\mail_queue;
use exception\missing_parameter_exception;

class mail_confirmation_link
{
	private $request;
	private $token_url;	
	private $mail_queue;

	private $data;
	private $template;
	private $route;

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

	public function set_data(array $data):mail_confirmation_link
	{
		$this->data = $data;
		return $this;
	}

	public function set_template(string $template):mail_confirmation_link
	{
		$this->template = $template;
		return $this;
	}

	public function set_route(string $route):mail_confirmation_link
	{
		$this->route = $route;
		return $this;
	}

	public function queue()
	{
		$this->queue_param($this->data, $this->template, $this->route);
		unset($this->data, $this->template, $this->route);
		return;
	}

	public function queue_param(array $data, string $template, string $route)
	{
		if (!isset($data['email']))
		{
			throw new missing_parameter_exception(sprintf(
				'No email set in %s in class %s', json_encode($data), __CLASS__
			));
		}

		$confirmation_link = $this->token_url->gen($route, $data);

		$vars = array_merge($data, [
			'confirmation_link'		=> $confirmation_link,
		]);

		$mail_data = [
			'to'		=> $data['email'],
			'template'	=> $template,
			'vars'		=> $vars,
		];

		$schema = $this->request->attributes->get('schema');

		if (isset($schema))
		{
			$mail_data['schema'] = $schema;
		}
		else
		{
			$mail_data['no_schema'] = true;
		}

		$this->mail_queue->put($mail_data, 1000000);
		return;
	}

	public function get():array
	{
		return $this->token_url->get();
	}
}
