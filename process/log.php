<?php

if (php_sapi_name() !== 'cli')
{
	echo '-- cli only --';
	exit;
}

$rootpath = '../';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../include/default.php';

$app['monitor_process']->boot();

while (true)
{
	if (!$app['monitor_process']->wait_most_recent(5))
	{
		continue;
	}

	$app['log_db']->update();
	$app['monitor_process']->periodic_log(10000);
}