<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class user_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $user = $app['controllers_factory'];

        $user->get('/{user_type}', 'controller\\user::index')
            ->value('user_type', 'active')
            ->bind('user_no_view');

        $user->get('/{user_type}/{view}', 'controller\\user::index')
            ->value('user_type', 'active')
            ->bind('user_index');

        $user->get('/self/{view}', 'controller\\user::show_self')
            ->bind('user_self');
        
        $user->get('/{user_type}/{user}', 'controller\\user::show')
            ->convert('user', 'user_converter:get')
            ->bind('user_show');

 /*           
        $user->get('/{user_type}/map', 'controller\\user::map')
            ->value('user_type', 'active')
            ->bind('user_map');

        $user->get('/{user_type}/tile', 'controller\\user::tile')
            ->value('user_type', 'active')
            ->bind('user_tile');
*/        
        $user->match('/add', 'controller\\user::add')
            ->bind('user_add');
        $user->match('/{user}/edit', 'controller\\user::edit')
            ->convert('user', 'user_converter:get')
            ->bind('user_edit');
        $user->match('/{user}/del', 'controller\\user::del')
            ->convert('user', 'user_converter:get')
            ->bind('user_del');

        $user->get('/typeahead/{user_type}', 'controller\\user_typeahead::get')
            ->bind('user_typeahead');
        $user->get('/typeahead-interlets/{user}', 
            'controller\\user_typeahead::get_interlets')
        //	->convert('user', 'user_converter:get')
            ->bind('user_interlets_typeahead');
        $user->get('/weighted-balance/{user}/{days}', 
            'controller\\user::weighted_balance')
            ->assert('days', '\d+')
        //	->convert('user', 'user_converter:get')
            ->bind('user_weighted_balance');

        $user->assert('user_type', 'active|new|leaving|direct|interlets|pre-active|post-active|all')
            ->assert('view', 'list|map|tiles')
            ->assert('user', '\d+');

        return $user;
    }
}