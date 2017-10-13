<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class permission_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $pms = $app['controllers_factory'];

        $pms->get('/', 'controller\\permission::index')
            ->bind('permission');

        return $pms;
    }
}