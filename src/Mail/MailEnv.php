<?php

namespace App\Mail;

class MailEnv
{
	private $noreplyAddress;
	private $fromAddress;
	private $hosterAddress;

	public function __construct(string $noreplyAddress, string $fromAddress, string $hosterAddress)
	{


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