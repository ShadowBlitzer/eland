<?php

namespace mail;

use service\token_url;
use mail\mail_validated;

class mail_validated_confirm_link
{
	private $token_url;	
	private $mail_validated;

	public function __construct(	
		token_url $token_url,
		mail_validated $mail_validated
	)
	{
		$this->token_url = $token_url;
		$this->mail_validated = $mail_validated;
	}

	public function get():array
	{
		$data = $this->token_url->get();

		if (count($data) && isset($data['email']))
		{
			$schema = $data['schema'] ?? 'no_schema';
			$this->mail_validated->set($data['email'], $schema, $data);
		}

		return $data;
	}
}
