<?php declare(strict_types=1);

namespace App\Mail;

use App\Service\Config;
use App\Mail\MailEnv;

use App\Exception\MethodCallOrderException;

class MailFrom
{
	private $config;
	private $mailEnv;
	private $siteName;

	private $schema;
	private $replyPossible;

	public function __construct(
		Config $config, 
		MailEnv $mailEnv, 
		string $siteName
	)
	{
		$this->config = $config;
		$this->mailEnv = $mailEnv;
		$this->siteName = $siteName;
	}

	public function setSchema(string $schema):MailFrom
	{
		if (isset($this->schema))
		{
			throw new MethodCallOrderException(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}
	
		$this->schema = $schema;
		return $this;
	}

	public function setReplyPossible(bool $replyPossible):MailFrom
	{
		if (isset($this->replyPossible))
		{
			throw new MethodCallOrderException(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}

		$this->replyPossible = $replyPossible;

		return $this;
	}

	public function get():array
	{
		if (isset($this->replyPossible) && $this->replyPossible)
		{
			$from = $this->mailEnv->getFrom();
		}
		else
		{
			$from = $this->mailEnv->getNoreply();
		}

		if (isset($this->schema))
		{
			$from = [$from => $this->config->get('systemname', $this->schema)];
		}
		else
		{
			$from = [$from => $this->siteName];
		}

		return $from;
	}

	public function clear():MailFrom
	{
		unset($this->schema, $this->replyPossible);
		return $this;
	}
}
