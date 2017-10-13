<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class transaction_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $transaction = $app['controllers_factory'];
        
        $transaction->get('/', 'controller\\transaction::index')
            ->bind('transaction_index');
        $transaction->get('/self', 'controller\\transaction::show_self')
            ->bind('transaction_self');
        $transaction->get('/{transaction}', 'controller\\transaction::show')
            ->convert('transaction', 'transaction_converter:get')
            ->bind('transaction_show');
        $transaction->match('/add', 'controller\\transaction::add')
            ->bind('transaction_add');
        $transaction->get('/{transaction}/edit', 'controller\\transacion::edit')
            ->convert('transaction', 'transaction_converter:get')
            ->bind('transaction_edit');
        $transaction->match('/add-interlets', 'controller\\transaction::add_interlets')
            ->bind('transaction_add_interlets');
        $transaction->match('/{transaction}/edit', 'controller\\transaction::edit')
            ->bind('transaction_edit');
        $transaction->get('/plot-user/{user}/{days}', 'controller\\transaction::plot_user')
            ->assert('user', '\d+')
            ->assert('days', '\d+')
            ->value('days', 365)
            ->bind('transaction_plot_user');
        $transaction->get('/sum-in/{days}', 'controller\\transaction::sum_in')
            ->assert('days', '\d+')
            ->value('days', 365)
            ->bind('transaction_sum_in');
        $transaction->get('/sum-out/{days}', 'controller\\transaction::sum_out')
            ->assert('days', '\d+')
            ->value('days', 365)
            ->bind('transaction_sum_out');
        
        $transaction->assert('transaction', '\d+');

        return $transaction;
    }
}