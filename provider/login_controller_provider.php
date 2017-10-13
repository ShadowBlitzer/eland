<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class login_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $login = $app['controllers_factory'];
        
        $login->match('/', 'controller\\login::form')
            ->bind('login');
        
        return $login;
    }
}