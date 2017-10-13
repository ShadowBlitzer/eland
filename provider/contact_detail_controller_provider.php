<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class contact_detail_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $contact_detail = $app['controllers_factory'];
        
        $contact_detail->get('/', 'controller\\contact_detail::index')
            ->bind('contactdetail_index');
        $contact_detail->match('/add', 'controller\\contact_detail::add')
            ->bind('contactdetail_add');
        $contact_detail->match('/{contact_detail}/edit', 'controller\\contact_detail::edit')
            ->assert('contact_detail', '\d+')
            ->convert('contact_detail', 'contact_converter:get')
            ->bind('contactdetail_edit');
        
        return $contact_detail;
    }
}