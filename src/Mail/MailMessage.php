<?php

namespace App\Mail;

use Swift_Mailer as Mailer;
use Monolog\Logger;

class MailMessage
{
	private $mailer;
	private $monolog;

	private $message;
	private $schema;

	public function __construct(Mailer $mailer, Logger $monolog)
	{
		$this->mailer = $mailer;
		$this->monolog = $monolog;
	}

	public function init():MailMessage
	{
		$this->message = new \Swift_Message();
		return $this;
	}

	public function setSchema(string $schema):MailMessage 
	{
		$this->schema = $schema;
		return $this;
	}

	public function setSubject(string $subject):MailMessage 
	{
		$this->message->setSubject($subject);
		return $this;
	}

	public function setFrom(array $from):MailMessage 
	{
		$this->message->setFrom($from);
		return $this;
	}

	public function setTo(array $to):MailMessage
	{
		$this->message->setTo($to);
		return $this;
	}

	public function setReplyTo(array $replyTo):MailMessage
	{
		$this->message->setReplyTo($replyTo);
		return $this;
	}

	public function setCc(array $cc):MailMessage
	{
		$this->message->setCc($cc);
		return $this;
	}

	public function setText(string $text):MailMessage
	{
		$this->message->setBody($text);
		return $this;
	}

	public function setHtml(string $html):MailMessage
	{
		$this->message->addPart($html, 'text/html');
		return $this;	
	}

	public function send()
	{
		$failedRecipients = [];
		$monologVars = isset($this->schema) ? ['schema' => $this->schema] : [];
		
		if ($this->mailer->send($this->message, $failedRecipients))
		{
			$this->monolog->info(sprintf('mail send: %s, to %s', 
				$this->message->getSubject(), json_encode($this->message->getTo())), $monologVars);
		}
		else
		{
			$this->monolog->error(sprintf('failed sending mail %s, failed recipients: %s', 
				$this->message->getSubject(), json_encode($failedRecipients)), $monologVars);
		}

		$this->mailer->getTransport()->stop();
	
		unset($this->message, $this->schema);
	}
}