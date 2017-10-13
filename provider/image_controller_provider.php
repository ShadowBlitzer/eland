<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class image_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $img = $app['controllers_factory'];
        
        $img->post('/', 'controller\\img::create')
            ->bind('img_create');
        $img->delete('/{img}', 'controller\\img::del')
            ->convert('img', 'service\\xdb::get')
            ->bind('img_del');
        $img->match('/{ad}/del', 'controller\\acccount::del_form')
            ->assert('ad', '[a-z0-9][a-z0-9-]{10}[a-z0-9]')
            ->convert('ad', 'service\\xdb::get')
            ->bind('img_del_form');
        
        $img->assert('img', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

        return $img;
        
    }
}