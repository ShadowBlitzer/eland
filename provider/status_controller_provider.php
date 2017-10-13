<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class status_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $status = $app['controllers_factory'];
        
        $status->get('/', 'controller\\status::index')
            ->bind('status');

        return $status;
    }
}