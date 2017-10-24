<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class ad_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $ad = $app['controllers_factory'];
        
        $ad->get('/', 'controller\\ad::index')
            ->bind('ad_index');
        $ad->get('/self', 'controller\\ad::show_self')
            ->bind('ad_self');
        $ad->get('/{ad}', 'controller\\ad::show')
            ->convert('ad', 'ad_converter:get')
            ->bind('ad_show');
        $ad->match('/add', 'controller\\ad::add')
            ->bind('ad_add');
        $ad->match('/{ad}/edit', 'controller\\ad::edit')
            ->convert('ad', 'ad_converter:get')
            ->bind('ad_edit');
        $ad->match('/{ad}/del', 'controller\\ad::del')
            ->convert('ad', 'ad_converter:get')
            ->bind('ad_del');
        
        $ad->assert('ad', '\d+');
        
        return $ad;
    }
}