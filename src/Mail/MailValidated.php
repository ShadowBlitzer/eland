<?php

namespace App\Mail;

use App\Service\Xdb;

class MailValidated
{
	private $xdb;

	public function __construct(Xdb $xdb)
	{
		$this->xdb = $xdb;
	}

	public function set(string $email, string $schema, array $data = []):MailValidated
	{
		$this->xdb->set('email_validated', $email, $data, $schema);
		return $this;
	}

	public function get(string $email, string $schema):bool
	{
		return count($this->xdb->get('email_validated', $email, $schema)) ? true : false;
	}
}
