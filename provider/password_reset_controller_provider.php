<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class password_reset_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $pwr = $app['controllers_factory'];
        
        $pwr->match('/', 'controller\\password_reset::form')
            ->bind('password_reset');
        $pwr->match('/{token}', 'controller\\password_reset::new_password')
            ->bind('password_reset_new_password');
        
        $pwr->assert('token', '[a-z0-9][a-z0-9-]{14}[a-z0-9]');
        
        return $pwr;
    }
}