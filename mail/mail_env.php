<?php

namespace mail;

use exception\invalid_mail_address_exception;
use exception\configuration_exception;

class mail_env
{
	private $noreply_address;
	private $from_address;
	private $hoster_address;

	public function __construct(string $noreply_address, string $from_address, string $hoster_address)
	{
		if (!$noreply_address)
		{
			throw new configuration_exception(sprintf(
				!'no mail noreply addres env set, class %s', __CLASS__));
			return;
		}

		if (!filter_var($noreply_address, FILTER_VALIDATE_EMAIL))
		{
			throw new invalid_mail_address_exception(sprintf(
				'no valid mail noreply addres env set, "%s", class %s', $noreply_address, __CLASS__));
			return;
		}

		if (!$from_address)
		{
			throw new configuration_exception(sprintf(
				!'no mail from addres env set, class %s', __CLASS__));
			return;
		}

		if (!filter_var($from_address, FILTER_VALIDATE_EMAIL))
		{
			throw new invalid_mail_address_exception(sprintf(
				'no valid mail from addres env set, "%s", class %s', $from_address, __CLASS__));
			return;
		}
	
		if (!$hoster_address)
		{
			throw new configuration_exception(sprintf(
				!'no mail hoster addres env set, class %s', __CLASS__));
			return;
		}

		if (!filter_var($hoster_address, FILTER_VALIDATE_EMAIL))
		{
			throw new invalid_mail_address_exception(sprintf(
				'no valid mail hoster addres env set, "%s", class %s', $hoster_address, __CLASS__));
			return;
		}

		$this->noreply_address = $noreply_address;
		$this->from_address = $from_address;
		$this->hoster_address = $hoster_address;
	}

	public function get_noreply():string
	{
		return $this->noreply_address;
	}

	public function get_from():string
	{
		return $this->from_address;
	}

	public function get_hoster():string 
	{
		return $this->hoster_address;
	}
}
