<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class public_page_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $page = $app['controllers_factory'];    

        $page->get('/{page}', 'controller\\page::show')
            ->value('page', 'index')
            ->assert('page', '|[a-z0-9-]{3,}')
            ->convert('page', 'page_converter:get')
            ->bind('page_show');

        return $page;
    }
}