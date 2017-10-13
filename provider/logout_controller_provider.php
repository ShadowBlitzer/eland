<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class logout_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $logout = $app['controllers_factory'];
        
        $logout->get('/', 'controller\\logout::logout')
            ->bind('logout');
          
        return $logout;
    }
}