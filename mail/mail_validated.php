<?php

namespace mail;

use service\xdb;

class mail_validated
{
	private $xdb;

	public function __construct(xdb $xdb)
	{
		$this->xdb = $xdb;
	}

	public function set(string $email, string $schema)
	{
		$this->xdb->set('email_validated', $email, [], $schema);
		return;
	}

	public function get(string $email, string $schema):bool
	{
		return count($this->xdb->get('email_validated', $email, $schema)) ? true : false;
	}
}
