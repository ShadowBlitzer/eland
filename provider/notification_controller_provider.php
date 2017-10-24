<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class notification_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $notification = $app['controllers_factory'];
        
        $notification->get('/', 'controller\\notification::index')
            ->bind('notification_index');
        $notification->get('/self', 'controller\\notification::show_self')
            ->bind('notification_self');
        $notification->get('/{notification}', 'controller\\notification::show')
            ->convert('notification', 'notification_converter:get')
            ->bind('notification_show');
        $notification->match('/add', 'controller\\notification::add')
            ->bind('notification_add');
        $notification->match('/{notification}/edit', 'controller\\notification::edit')
            ->convert('notification', 'notification_converter:get')
            ->bind('notification_edit');
        $notification->match('/{notification}/del', 'controller\\notification::del')
            ->convert('notification', 'notification_converter:get')
            ->bind('notification_del');
        
        $notification->assert('notification', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

        return $notification;
    }
}