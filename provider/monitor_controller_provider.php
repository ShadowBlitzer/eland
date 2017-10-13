<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class monitor_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $monitor = $app['controllers_factory'];
        
        $monitor->get('/', 'controller\\main::monitor')
            ->bind('monitor');
        
        return $monitor;
    }
}