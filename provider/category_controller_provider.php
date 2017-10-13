<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class category_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $cat = $app['controllers_factory'];
        
        $cat->get('/', 'controller\\category::index')
            ->bind('category_index');
        $cat->match('/add/{parent_category}', 'controller\\category::add')
            ->value('parent_category', 0)
            ->assert('parent_category', '\d+')
            ->bind('category_add');
        $cat->match('/{category}/edit', 'controller\\category::edit')
            ->convert('category', 'category_converter:get')
            ->assert('category', '\d+')
            ->bind('category_edit');
        $cat->match('/{category}/del', 'controller\\category::del')
            ->assert('category', '\d+')
            ->convert('category', 'category_converter:get')
            ->bind('category_del');

        return $cat;
    }
}