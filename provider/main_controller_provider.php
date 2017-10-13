<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class main_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $main = $app['controllers_factory'];
        
        $main->get('/', 'controller\\main::index')
            ->bind('main_index');
        
        return $main;
    }
}