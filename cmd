#!/usr/bin/env php
<?php

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

set_time_limit(0);

if (!getenv('DATABASE_URL'))
{
    $env = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($env as $e)
    {
        putenv($e);
    }
}

$app = require_once __DIR__ . '/app.php';

$app->on(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) use ($app) {
    $app['request_context']->setHost(getenv('ROUTER_HOST'));
    $app['request_context']->setScheme(getenv('ROUTER_SCHEME') ?: 'https');
    $app['request_context']->setBaseUrl(null);
});

ob_start();
$app->run();
ob_end_clean();

$console = $app['console'];

$console->add(new command\migrate_elas());
$console->add(new command\migrate_status());
$console->add(new command\process_worker());
$console->add(new command\process_mail());
$console->add(new command\process_sync());
$console->add(new command\process_geo());

$console->run();