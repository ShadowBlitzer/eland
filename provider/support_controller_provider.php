<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class support_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $support = $app['controllers_factory'];
        
        $support->match('/', 'controller\\support::form')
            ->bind('support');

        return $support;
    }
}