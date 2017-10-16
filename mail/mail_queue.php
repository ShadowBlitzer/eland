<?php

namespace mail;

use Monolog\Logger;
use service\queue;
use service\config;

class mail_queue
{
	private $monolog;
	private $queue;
	private $config;

	private $template;
	private $vars;
	private $to;
	private $reply_to;
	private $cc;
	private $priority = 0;
	private $schema;

	public function __construct(Logger $monolog, queue $queue, config $config)
	{
		$this->monolog = $monolog;
		$this->queue = $queue;
		$this->config = $config;
	}

	public function set_template(string $template):mail_queue
	{
		$this->template = $template;
		return $this;
	}

	public function set_vars(array $vars):mail_queue
	{
		$this->vars = $vars;
		return $this;
	}

	public function set_to(array $to):mail_queue
	{
		$this->to = $to;
		return $this;
	}

	public function set_reply_to(array $reply_to):mail_queue
	{
		$this->reply_to = $reply_to;
		return $this;
	}

	public function set_cc(array $cc):mail_queue
	{
		$this->cc = $cc;
		return $this;
	}

	public function set_priority(int $priority):mail_queue
	{
		$this->priority = $priority;
		return $this;
	}

	public function set_schema(string $schema):mail_queue
	{
		$this->schema = $schema;
		return $this;
	}

	public function put()
	{
		$data = [];
	
		if (isset($this->schema))
		{
			if (!$this->config->get('mailenabled', $this->schema))
			{
				$this->monolog->info(sprintf(
					'mail functionality not enabled in 
					configuration, mail not queued: %s', json_encode($data)), [
						'schema'	=> $this->schema,
					]
				);

				return;
			}

			$data['schema'] = $this->schema;
		}

		if (isset($this->cc))
		{
			$data['cc'] = $this->cc;
		}

		if (isset($this->reply_to))
		{
			$data['reply_to'] = $this->reply_to;
		}
		 
		$this->put_param($this->template, $this->vars, $this->to, $data, $this->priority);

		unset($this->template, $this->vars, $this->to, $this->reply_to, $this->cc, $this->schema);
		$this->priority = 0;
	}

	public function put_param(string $template, array $vars, array $to, array $data = [], int $priority = 0)
	{
		$data = array_merge($data, [
			'template'		=> $template,
			'vars'			=> $vars,
			'to'			=> $to,
		]);

		$this->queue->set('mail', $data, $priority);
	}
}
