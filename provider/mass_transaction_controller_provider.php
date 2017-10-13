<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class mass_transaction_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $mass_transaction = $app['controllers_factory'];
        
        $mass_transaction->match('/', 'controller\\mass_transaction::form')
            ->bind('mass_transaction');
        
        return $mass_transaction;
    }
}