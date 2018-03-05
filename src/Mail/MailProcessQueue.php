<?php

namespace App\Mail;

use App\Service\Queue;
use App\Mail\MailEnabled;
use App\Mail\MailProcessRecord;

class MailProcessQueue
{
	private $queue;
	private $mailEnabled;
	private $mailProcessRecord;

	public function __construct(Queue $queue, MailEnabled $mailEnabled, MailprocessRecord $mailProcessRecord)
	{
		$this->queue = $queue;
		$this->mailEnabled = $mailEnabled;
		$this->mailProcessRecord = $mailProcessRecord;
	}

	public function run():MailProcessQueue
	{
		$record = $this->queue->get('mail');

		if (!count($record))
		{
			return $this;
		}

		if (!$this->mailEnabled->isEnabled($record))
		{
			return $this;
		}

		$this->mailProcessRecord->process($record);

		return $this;
	}
}
