<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class contact_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $contact = $app['controllers_factory'];
        
        $contact->match('/', 'controller\\contact::form')
            ->bind('contact');
        $contact->get('/{token}', 'controller\\contact::confirm')
            ->bind('contact_confirm');

        $contact->assert('token', '[a-z0-9][a-z0-9-]{14}[a-z0-9]');
        
        return $contact;
    }
}