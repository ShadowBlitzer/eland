<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class log_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $log = $app['controllers_factory'];
        
        $log->get('/', 'controller\\log::index')
            ->bind('log');
        $log->get('/typeahead', 'controller\\log::typeahead')
            ->bind('log_typeahead');
        
        return $log;   
    }
}