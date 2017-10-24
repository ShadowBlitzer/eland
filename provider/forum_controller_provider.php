<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class forum_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $forum = $app['controllers_factory'];
        
        $forum->get('/', 'controller\\forum::index')
            ->bind('forum_index');
        $forum->get('/{forum}', 'controller\\forum::show')
            ->convert('forum', 'forum_converter:get')
            ->bind('forum_show');
        $forum->match('/add', 'controller\\forum::add')
            ->bind('forum_add');
        $forum->match('/{forum}/edit', 'controller\\forum::edit')
            ->convert('forum', 'forum_converter:get')
            ->bind('forum_edit');
        $forum->match('/{forum}/del', 'controller\\forum::del')
            ->convert('forum', 'forum_converter:get')
            ->bind('forum_del');     
          
        $forum->assert('forum', '[a-z0-9][a-z0-9-]{10}[a-z0-9]');

        return $forum;
    }
}