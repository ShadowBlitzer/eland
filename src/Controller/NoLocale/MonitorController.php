<?php

namespace App\Controller\NoLocale;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection as Db;
use Predis\Client as Predis;

class MonitorController extends AbstractController
{
	/**
	* @Route("/monitor", name="monitor")
	* @Method("GET")
	*/
	public function status(LoggerInterface $logger, Db $db, Predis $predis, Request $request):Response
	{
		try
		{
			$db->fetchColumn('select max(agg_id) from xdb.events');
		}
		catch(\Exception $e)
		{
			throw new ServiceUnavailableHttpException('db fail: ' . $e->getMessage());
		}

		try
		{
			$predis->incr('eland_monitor');
			$predis->expire('eland_monitor', 400);
		}
		catch(\Exception $e)
		{
			throw new ServiceUnavailableHttpException('redis fail: ' . $e->getMessage());			
		}

		$monitorCount = $predis->get('eland_monitor');

		if ($monitorCount > 2)
		{
			$monitorServiceWorker = $predis->get('monitor_service_worker');

			if (!$monitorServiceWorker)
			{
				throw new ServiceUnavailableHttpException('service worker is down');					
			}		
		}

        return new Response('<html><body>Ok</body></html>');		
	}
}
