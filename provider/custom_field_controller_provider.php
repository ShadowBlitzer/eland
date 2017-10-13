<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class custom_field_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $cust = $app['controllers_factory'];
        
        $cust->get('/', 'controller\\custom_field::index')
            ->bind('custom_field_index');
        $cust->match('/add', 'controller\\custom_field::add')
            ->bind('custom_field_add');
        $cust->match('/{custom_field}/edit', 'controller\\custom_field::edit')
            ->convert('custom_field', 'custom_field_converter:get')
            ->bind('custom_field_edit');

        return $cust;
    }
}