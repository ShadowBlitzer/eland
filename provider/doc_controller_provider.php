<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class doc_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $doc = $app['controllers_factory'];
        
        $doc->get('/', 'controller\\doc::index')
            ->bind('doc_index');
        $doc->get('/{doc}', 'controller\\doc::show')
            ->convert('doc', 'doc_converter:get')
            ->bind('doc_show');
        $doc->match('/add', 'controller\\doc::add')
            ->bind('doc_add');
        $doc->match('/{doc}/edit', 'controller\\doc::edit')
            ->convert('doc', 'doc_converter:get')
            ->bind('doc_edit');
        $doc->get('/typeahead', 'controller\\doc::typeahead')
            ->bind('doc_typeahead');
        
        $doc->assert('doc', '^[a-z0-9][a-z0-9-]{10}[a-z0-9]$');

        return $doc;      
    }
}