<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class export_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $export = $app['controllers_factory'];
        
        $export->match('/', 'controller\\export::index')
            ->bind('export');
        
        return $export;
    }
}