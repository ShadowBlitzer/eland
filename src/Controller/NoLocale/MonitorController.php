<?php

namespace App\Controller\NoLocale;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Predis\Client as Predis;
use Doctrine\DBAL\Driver\Connection as Db;

class MonitorController extends AbstractController
{
    /**
     * @Route("/monitor", name="monitor")
     */
    public function index(Request $request, Db $db, Predis $predis):Response
    {
        try
        {
            $db->fetchColumn('select min(id) from users');
        }
        catch(Exception $e)
        {
            echo 'db fail';
            error_log('db_fail: ' . $e->getMessage());
            throw $e;
            exit;
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
                    error_log('monitor worker: ' . $monitor_service_worker);
                }
                else
                {
                    http_response_code(503);
                    echo 'web service is up but service worker is down';
                    exit;
                }
            }
        }
        catch(Exception $e)
        {
            echo 'redis fail';
            error_log('redis_fail: ' . $e->getMessage());
            throw $e;
            exit;
        }

        return $this->render('monitor/index.html.twig', [
            'controller_name' => 'MonitorController',
        ]);
    }
}
