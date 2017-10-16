<?php

namespace mail;

use exception\invalid_mail_address_exception;

abstract class mail_system_address
{
	private $mail_address;

	public function __construct(string $mail_address)
	{
		if (!filter_var($mail_address, FILTER_VALIDATE_EMAIL))
		{
			throw new invalid_mail_address_exception(sprintf(
				'no valid mail addres env set, "%s" in class %s', $mail_address, __CLASS__));
			return;
		}

		$this->mail_address = $mail_address;
	}

	public function get():string
	{
		return $this->mail_address;
	}
}
