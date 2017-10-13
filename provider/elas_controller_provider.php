<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class elas_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $elas = $app['controllers_factory'];
        
        $elas->get('/soap-status/{user}', 'controller\\elas::soap_status')
            ->convert('user', 'user_converter:get')
            ->bind('elas_soap_status');
        $elas->get('/group-login/{user}', 'controller\\elas::soap_status')
            ->convert('user', 'user_converter:get')
            ->bind('elas_group_login');
        
        $elas->assert('user', '\d+');
     
        return $elas;
    }
}