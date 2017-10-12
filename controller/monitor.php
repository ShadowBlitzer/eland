<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class monitor
{
	public function get(Request $request, app $app)
	{
		try
		{
			$app['db']->fetchColumn('select max(id) from xdb.events');
		}
		catch(Exception $e)
		{
			http_response_code(503);
			$app['monolog']->error('db_fail: ' . $e->getMessage());
			throw $e;
			exit;
		}

		try
		{
			$app['predis']->incr('eland_monitor');
			$app['predis']->expire('eland_monitor', 400);

			$monitor_count = $app['predis']->get('eland_monitor');

			if ($monitor_count > 2)
			{
				$monitor_service_worker = $app['predis']->get('monitor_service_worker');

				if (!$monitor_service_worker)
				{
					http_response_code(503);
					$app['monolog']->error('web service is up but service worker is down')d;
					exit;
				}
			}
		}
		catch(Exception $e)
		{
			$app['monolog']->error('redis_fail: ' . $e->getMessage());
			throw $e;
			exit;
		}

		exit;

	}
}
