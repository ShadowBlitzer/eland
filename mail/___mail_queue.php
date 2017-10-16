<?php

namespace mail;

use Monolog\Logger;
use service\queue;
use service\config;

use exception\missing_schema_exception;

class mail_queue
{
	private $monolog;
	private $queue;
	private $config;

	public function __construct(Logger $monolog, queue $queue, config $config)
	{
		$this->monolog = $monolog;
		$this->queue = $queue;
		$this->config = $config;
	}

	public function put(array $data, int $priority = 0)
	{
		if (!isset($data['no_schema']))
		{
			if (!isset($data['schema']))
			{
				throw new missing_schema_exception(
					'Schema is missing in mail ' . json_encode($data)
				);
			}

			if (!$this->config->get('mailenabled', $data['schema']))
			{
				$this->monolog->info(sprintf(
					'mail functionality not enabled in 
					configuration, mail not queued: %s', json_encode($data)), [
						'schema'	=> $data['schema'],
					]
				);

				return;
			}
		}

		if (!isset($data['template']))
		{
			throw new missing_parameter_exception(
				'No template set in mail ' . json_encode($data)
			);
		}

		if (!isset($data['vars']) || !is_array($data['vars']))
		{
			throw new missing_parameter_exception(
				'No vars array set in mail ' . json_encode($data)
			);			
		}
	
		if (!isset($data['to']))
		{
			throw new missing_parameter_exception(
				'No "to" set in mail ' . json_encode($data)
			);	
		}

		$this->queue->set('mail', $data, $priority);
	}
}
