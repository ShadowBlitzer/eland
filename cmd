#!/usr/bin/env php
<?php

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

$console = $app['console'];

$console->add(new command\migrate_elas());
$console->add(new command\migrate_status());

$console->run();