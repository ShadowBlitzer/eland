<?php

namespace mail;

use service\config;
use mail\mail_env;

use exception\method_call_order_exception;

class mail_from
{
	private $config;
	private $mail_env;
	private $site_name;

	private $schema;
	private $reply_possible;

	public function __construct(
		config $config, 
		mail_env $mail_env, 
		string $site_name
	)
	{
		$this->config = $config;
		$this->mail_env = $mail_env;
		$this->site_name = $site_name;
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
			$from = $this->mail_env->get_from();
		}
		else
		{
			$from = $this->mail_env->get_noreply();
		}

		if (isset($this->schema))
		{
			$from = [$from => $this->config->get('systemname', $this->schema)];
		}
		else
		{
			$from = [$from => $this->site_name];
		}

		return $from;
	}

	public function clear()
	{
		unset($this->schema, $this->reply_possible);
	}
}
