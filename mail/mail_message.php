<?php

namespace mail;

use Swift_Mailer as mailer;
use Monolog\Logger;

class mail_message
{
	private $mailer;
	private $monolog;

	private $message;
	private $schema;

	public function __construct(mailer $mailer, Logger $monolog)
	{
		$this->mailer = $mailer;
		$this->monolog = $monolog;
	}

	public function init():mail_message
	{
		$this->message = new \Swift_Message();
		return $this;
	}

	public function set_schema(string $schema):mail_message 
	{
		$this->schema = $schema;
		return $this;
	}

	public function set_subject(string $subject):mail_message 
	{
		$this->message->setSubject($subject);
		return $this;
	}

	public function set_from(array $from):mail_message 
	{
		$this->message->setFrom($from);
		return $this;
	}

	public function set_to(array $to):mail_message
	{
		$this->message->setTo($to);
		return $this;
	}

	public function set_reply_to(array $reply_to):mail_message
	{
		$this->message->setReplyTo($reply_to);
		return $this;
	}

	public function set_cc(array $cc):mail_message
	{
		$this->message->setCc($cc);
		return $this;
	}

	public function set_text(string $text):mail_message
	{
		$this->message->setBody($text);
		return $this;
	}

	public function set_html(string $html):mail_message
	{
		$this->message->addPart($html, 'text/html');
		return $this;	
	}

	public function send()
	{
		$failed_recipients = [];
		$monolog_vars = isset($this->schema) ? ['schema' => $this->schema] : [];
		
		if ($this->mailer->send($this->message, $failed_recipients))
		{
			$this->monolog->info(sprintf('mail send: %s, to %s', 
				$this->message->getSubject(), json_encode($this->message->getTo())), $monolog_vars);
		}
		else
		{
			$this->monolog->error(sprintf('failed sending mail %s, failed recipients: %s', 
				$this->message->getSubject(), json_encode($failed_recipients)), $monolog_vars);
		}

		$this->mailer->getTransport()->stop();
	
		unset($this->message, $this->schema);
	}
}