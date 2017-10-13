<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class page_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $page = $app['controllers_factory'];

        $page->match('/', 'controller\\page::a_index')
            ->bind('page_a_index');
        $page->match('/add', 'controller\\page::a_add')
            ->bind('page_a_add');
        $page->get('/{page}', 'controller\\page::a_show')
            ->convert('page', 'page_converter:get')
            ->bind('page_a_show');
        $page->match('/{page}/edit', 'controller\\page::a_edit')
        //	->convert('page', 'page_converter:get')
            ->bind('page_a_edit');
        $page->match('/{page}/del', 'controller\\page::a_del')
            ->convert('page', 'page_converter:get')
            ->bind('page_a_del');
            
        $page->assert('page', '[a-z0-9-]{3,}');

        return $page;
    }
}