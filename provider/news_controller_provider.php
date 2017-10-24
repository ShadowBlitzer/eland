<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class news_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $news = $app['controllers_factory'];
        
        $news->match('/', 'controller\\news::index')
            ->bind('news_index');
        $news->get('/{news}', 'controller\\news::show')
            ->convert('news', 'news_converter:get')
            ->bind('news_show');
        $news->match('/add', 'controller\\news::add')
            ->bind('news_add');
        $news->match('/{news}/edit', 'controller\\news::edit')
            ->convert('news', 'news_converter:get')
            ->bind('news_edit');
        $news->match('/{news}/del', 'controller\\news::del')
            ->convert('news', 'news_converter:get')
            ->bind('news_del');
        
        $news->assert('news', '\d+');
        
        return $news;
    }
}