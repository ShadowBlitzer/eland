<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class hosting_request_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $hr = $app['controllers_factory'];
        
        $hr->match('/', 'controller\\hosting_request::form')
            ->bind('hosting_request');
        $hr->get('/{token}', 'controller\\hosting_request::confirm')
            ->bind('hosting_request_confirm');
        
        $hr->assert('token', '[a-z0-9][a-z0-9-]{14}[a-z0-9]');
        
        return $hr;
    }
}