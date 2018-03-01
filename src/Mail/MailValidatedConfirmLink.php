<?php

namespace App\Mail;

use App\Service\TokenUrl;
use App\Mail\MailValidated;

class MailValidatedConfirmLink
{
	private $tokenUrl;	
	private $mailValidated;

	public function __construct(	
		TokenUrl $tokenUrl,
		MailValidated $mailValidated
	)
	{
		$this->tokenUrl = $tokenUrl;
		$this->mailValidated = $mailValidated;
	}

	public function get():array
	{
		$data = $this->tokenUrl->get();

		if (count($data) && isset($data['email']))
		{
			$schema = $data['schema'] ?? 'no_schema';
			$this->mailValidated->set($data['email'], $schema, $data);
		}

		return $data;
	}
}
