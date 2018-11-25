<?php

namespace App\Controller\NoLocale;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Predis\Client as Predis;
use Doctrine\DBAL\Driver\Connection as Db;
use Psr\Log\LoggerInterface;

class MonitorController extends AbstractController
{
    /**
     * Route("/monitor", name="monitor")
     */
    public function index(
        Request $request,
        Db $db,
        Predis $predis,
        LoggerInterface $logger):Response
    {
        try
        {
            $tablename = $db->fetchColumn('select tablename from pg_catalog.pg_tables');
        }
        catch(Exception $e)
        {
            $logger->error('Database down: ' . $e->getMessage());
            return New Response('Database down', 503);
        }

        try
        {
            $predis->incr('eland_monitor');
            $predis->expire('eland_monitor', 400);
            $monitor_count = $predis->get('eland_monitor');

            if ($monitor_count > 2)
            {
                $monitor_service_worker = $predis->get('monitor_service_worker');

                if ($monitor_service_worker)
                {
                    $logger->debug('monitor worker: ' . $monitor_service_worker);
                }
                else
                {
                    $logger->error('Web service is up, but worker is down.');
                    return new Response('web service is up but service worker is down', 503);
                }
            }
        }
        catch(Exception $e)
        {
            $logger->error('Redis is down.');
            return new Response('Redis is down', 503);
        }

        return new Response('Ok (' . $tablename . ')');
    }
}
