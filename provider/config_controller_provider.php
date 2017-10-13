<?php

namespace provider;

use Silex\Application as app;
use Silex\Api\ControllerProviderInterface;

class config_controller_provider implements ControllerProviderInterface
{
    public function connect(app $app)
    {
        $config = $app['controllers_factory'];
        
        $config->match('/', 'controller\\config::index')
            ->bind('config_index');
        $config->match('/balance-limits', 'controller\\config::balance_limits')
            ->bind('config_balance_limits');
        $config->match('/ads', 'controller\\config::ads')
            ->bind('config_ads');
        $config->match('/naming', 'controller\\config::naming')
            ->bind('config_naming');
        $config->match('/mail-addresses', 'controller\\config::mail_addresses')
            ->bind('config_mail_addresses');
        $config->match('/periodic-mail', 'controller\\config::periodic_mail')
            ->bind('config_periodic_mail');
        $config->match('/contact-form', 'controller\\config::contact_form')
            ->bind('config_contact_form');
        $config->match('/registration-form', 'controller\\config::registration_form')
            ->bind('config_registration_form');
        $config->match('/forum', 'controller\\config::forum')
            ->bind('config_forum');
        $config->match('/members', 'controller\\config::members')
            ->bind('config_members');
        $config->match('/system', 'controller\\config::system')
            ->bind('config_system');
        
        return $config;
    }
}