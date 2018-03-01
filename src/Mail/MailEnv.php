<?php

namespace App\Mail;

use App\Exception\InvalidMailAddressException;
use App\Exception\ConfigurationException;

class MailEnv
{
	private $noreplyAddress;
	private $fromAddress;
	private $hosterAddress;

	public function __construct(string $noreplyAddress, string $fromAddress, string $hosterAddress)
	{
		if (!$noreplyAddress)
		{
			throw new ConfigurationException(sprintf(
				!'no mail noreply addres env set, class %s', __CLASS__));
			return;
		}

		if (!filter_var($noreplyAddress, FILTER_VALIDATE_EMAIL))
		{
			throw new InvalidMailAddressException(sprintf(
				'no valid mail noreply addres env set, "%s", class %s', $noreply_address, __CLASS__));
			return;
		}

		if (!$fromAddress)
		{
			throw new ConfigurationException(sprintf(
				!'no mail from addres env set, class %s', __CLASS__));
			return;
		}

		if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL))
		{
			throw new InvalidMailAddressException(sprintf(
				'no valid mail from addres env set, "%s", class %s', $from_address, __CLASS__));
			return;
		}
	
		if (!$hosterAddress)
		{
			throw new ConfigurationException(sprintf(
				!'no mail hoster addres env set, class %s', __CLASS__));
			return;
		}

		if (!filter_var($hosterAddress, FILTER_VALIDATE_EMAIL))
		{
			throw new InvalidMailAddressException(sprintf(
				'no valid mail hoster addres env set, "%s", class %s', $hoster_address, __CLASS__));
			return;
		}

		$this->noreplyAddress = $noreplyAddress;
		$this->fromAddress = $fromAddress;
		$this->hosterAddress = $hosterAddress;
	}

	public function getNoreply():string
	{
		return $this->noreply_address;
	}

	public function getFrom():string
	{
		return $this->from_address;
	}

	public function getHoster():string 
	{
		return $this->hoster_address;
	}
}
