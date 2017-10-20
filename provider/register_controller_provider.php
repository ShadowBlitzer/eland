<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class register_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $register = $app['controllers_factory'];
        
        $register->match('/', 'controller\\register::form')
            ->bind('register');
        $register->get('/{token}', 'controller\\register::confirm')
            ->bind('register_confirm');
        
        $register->assert('token', '[a-z0-9-]{16}');
        
        return $register;
    }
}