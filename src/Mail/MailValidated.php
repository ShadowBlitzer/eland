<?php declare(strict_types=1);

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
		$this->xdb->set('email_validated', $email, $schema, $data);
		return $this;
	}

	public function get(string $email, string $schema):bool
	{
		return $this->xdb->count('email_validated', $email, $schema) === 0 ? false : true;
	}
}