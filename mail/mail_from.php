<?php

namespace mail;

use service\config;
use mail\mail_from_address;
use mail\mail_noreply_address;

use exception\method_call_order_exception;

class mail_from
{
	private $config;
	private $mail_from_address;
	private $mail_noreply_address;

	private $schema;
	private $reply_possible;

	public function __construct(
		config $config, 
		mail_from_address $mail_from_address, 
		mail_noreply_address $mail_noreply_address
	)
	{
		$this->config = $config;
		$this->mail_from_address = $mail_from_address;
		$this->mail_noreply_address = $mail_noreply_address;
	}

	public function set_schema(string $schema):mail_from
	{
		if (isset($this->schema))
		{
			throw new method_call_order_exception(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}
	
		$this->schema = $schema;
		return $this;
	}

	public function set_reply_possible(bool $reply_possible):mail_from
	{
		if (isset($this->reply_possible))
		{
			throw new method_call_order_exception(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}

		$this->reply_possible = $reply_possible;
		return $this;
	}

	public function get():array
	{
		if (isset($this->reply_possible) && $this->reply_possible)
		{
			$from = $this->mail_from_address->get();
		}
		else
		{
			$from = $this->mail_noreply_address->get();
		}

		if (isset($this->schema))
		{
			$from = [$from => $this->config->get('systemname', $this->schema)];
		}
		else
		{
			$from = [$from];
		}

		return $from;
	}

	public function clear()
	{
		unset($this->schema, $this->reply_possible);
	}
}
