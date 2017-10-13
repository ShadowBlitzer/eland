<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class periodic_charge_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $periodic_charge = $app['controllers_factory'];
        
        $periodic_charge->match('/', 'controller\periodic_charge::form')
            ->bind('periodic_charge');
        
        return $periodic_charge;
    }
}