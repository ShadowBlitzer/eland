<?php

namespace App\Mail;

use Psr\Log\LoggerInterface;
use App\Service\Queue;
use App\Service\Config;

class MailQueue
{
	private $logger;
	private $queue;
	private $config;

	private $template;
	private $vars;
	private $to;
	private $replyTo;
	private $cc;
	private $priority = 0;
	private $schema;

	public function __construct(LoggerInterFace $logger, Queue $queue, Config $config)
	{
		$this->logger = $logger;
		$this->queue = $queue;
		$this->config = $config;
	}

	public function setTemplate(string $template):MailQueue
	{
		$this->template = $template;
		return $this;
	}

	public function setVars(array $vars):MailQueue
	{
		$this->vars = $vars;
		return $this;
	}

	public function setTo(array $to):MailQueue
	{
		$this->to = $to;
		return $this;
	}

	public function setReplyTo(array $replyTo):MailQueue
	{
		$this->replyTo = $replyTo;
		return $this;
	}

	public function setCc(array $cc):MailQueue
	{
		$this->cc = $cc;
		return $this;
	}

	public function setPriority(int $priority):MailQueue
	{
		$this->priority = $priority;
		return $this;
	}

	public function setSchema(string $schema):mailQueue
	{
		$this->schema = $schema;
		return $this;
	}

	public function put():MailQueue
	{
		$data = [];
	
		if (isset($this->schema))
		{
			if (!$this->config->get('mailenabled', $this->schema))
			{
				$this->logger->info(sprintf(
					'mail functionality not enabled in 
					configuration, mail not queued: %s', json_encode($data)), [
						'schema'	=> $this->schema,
					]
				);

				return $this;
			}

			$data['schema'] = $this->schema;
		}

		if (isset($this->cc))
		{
			$data['cc'] = $this->cc;
		}

		if (isset($this->replyTo))
		{
			$data['reply_to'] = $this->replyTo;
		}
		 
		$this->putParam($this->template, $this->vars, $this->to, $data, $this->priority);
		$this->clear();

		return $this;
	}

	public function clear():MailQueue
	{
		unset($this->template, $this->vars, $this->to, $this->replyTo, $this->cc, $this->schema);
		$this->priority = 0;	
		
		return $this;
	}

	public function putParam(string $template, array $vars, array $to, array $data = [], int $priority = 0):MailQueue
	{
		if (isset($data['schema']))
		{
			$vars['schema'] = $data['schema'];
		}

		if (!isset($data['reply_to']))
		{
			$vars['no_reply'] = true;
		}
	
		$data = array_merge($data, [
			'template'		=> $template,
			'vars'			=> $vars,
			'to'			=> $to,
		]);

		$this->queue->set('mail', $data, $priority);

		return $this;
	}
}
