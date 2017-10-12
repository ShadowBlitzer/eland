<?php

namespace mail;

use service\queue;
use service\config;

class mail_queue
{
	private $queue;
	private $config;

	public function __construct(queue $queue, config $config)
	{
		$this->queue = $queue;
		$this->config = $config;
	}

	public function put(array $data)
	{
		if (!isset($data['no_schema']))
		{
			if (!isset($data['schema']))
			{
				throw new missing_schema_exception(
					'Schema is missing in mail ' . json_encode($data)
				);
			}

			if (!$this->config('mailenabled', $data['schema']))
			{
				throw new configuration_exception(sprintf(
					'mail functionality not enabled in 
					configuration in schema %s for mail %s',
					$schema, json_encode($data)
				);
			}
		}

		if (!isset($data['template']))
		{
			throw new missing_parameter_exception(
				'No template set in mail ' . json_encode($data)
			);
		}

		if (!isset($data['subject']))
		{
			throw new missing_parameter_exception(
				'No subject set in mail ' . json_encode($data)
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

		if (!isset($data['priority']))
		{
			$data['priority'] = 1000;
		}

		$this->queue->set('mail', $data);
	}
}
