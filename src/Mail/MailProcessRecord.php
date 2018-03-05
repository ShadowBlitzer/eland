<?php

namespace App\Mail;

use App\Mail\MailMessage;
use App\Mail\MailTemplate;
use App\Mail\MailFrom;

class MailProcessRecord
{
	private $mailMessage;
	private $mailTemplate;
	private $mailFrom;

	public function __construct(MailMessage $mailMessage, MailTemplate $mailTemplate, MailFrom $mailFrom)
	{
		$this->mailMessage = $mailMessage;
		$this->mailTemplate = $mailTemplate;
		$this->mailFrom = $mailFrom;
	}

	public function process(array $record):MailProcessRecord
	{
		$this->mailMessage->init();

		if (isset($record['schema']))
		{
			$this->mailMessage->setSchema($record['schema']);
			$this->mailFrom->setSchema($record['schema']);			
		}

		$this->mailTemplate
			->setTemplate($record['template'])
			->setVars($record['vars']);

		if (isset($record['reply_to']))
		{
			$this->mailFrom->setReplyPossible(true);
			$this->mailMessage->setReplyTo($record['reply_to']);
		}

		if (isset($record['cc']))
		{
			$this->mailMessage->setCc($record['cc']);
		}

		$this->mailMessage->setTo($record['to'])
			->setFrom($this->mailFrom->get())
			->setText($this->mailTemplate->getText())
			->setHtml($this->mailTemplate->getHtml())
			->setSubject($this->mailTemplate->getSubject())
			->send();

		$this->mailFrom->clear();
		$this->mailTemplate->clear();

		return $this;
	}
}
