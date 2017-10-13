<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class type_contact_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $type_contact = $app['controllers_factory'];
        
        $type_contact->get('/', 'controller\\type_contact::index')
            ->bind('typecontact_index');
        $type_contact->match('/add', 'controller\\type_contact::add')
            ->bind('typecontact_add');
        $type_contact->match('/{type_contact}/edit', 'controller\\type_contact::edit')
            ->assert('type_contact', '\d+')
            ->convert('type_contact', 'type_contact_converter:get')
            ->bind('typecontact_edit');
        $type_contact->match('/{type_contact}/del', 'controller\\type_contact::del')
            ->assert('type_contact', '\d+')
            ->convert('type_contact', 'type_contact_converter:get')
            ->bind('typecontact_del');

        return $type_contact;
    }
}