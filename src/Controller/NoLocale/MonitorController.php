<?php

namespace App\Controller\NoLocale;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class MonitorController extends AbstractController
{
	/**
	* @Route("/monitor", name="monitor")
	* @Method("GET")
	*/
	public function status(Request $request):Response
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
					$app['monolog']->error('web service is up but service worker is down');
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
