<?php declare(strict_types=1);

namespace App\Mail;

use App\Service\Queue;
use App\Service\Config;
use Psr\Log\LoggerInterface;

class MailEnabled
{
	private $queue;
	private $config;
	private $logger;

	public function __construct(Queue $queue, Config $config, LoggerInterface $logger)
	{
		$this->queue = $queue;
		$this->config = $config;
		$this->logger = $logger;
	}

	public function isEnabled(array $record):bool
	{
		if (isset($record['schema']))
		{
			if (!$this->config->get('mailenabled', $record['schema']))
			{
				$this->logger->debug(sprintf(
					'mail functionality not enabled in 
					configuration, mail from queue not send: %s', json_encode($record)), [
						'schema'	=> $record['schema'],
					]
				);

				return false;
			}
		}
		
		return true;
	}
}
