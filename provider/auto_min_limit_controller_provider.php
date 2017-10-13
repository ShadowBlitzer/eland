<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class auto_min_limit_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $auto_min_limit = $app['controllers_factory'];
        
        $auto_min_limit->match('/', 'controller\\auto_min_limit::form')
            ->bind('auto_min_limit');
        
        return $auto_min_limit;
    }
}