<?php

if (php_sapi_name() !== 'cli')
{
	echo '-- cli only --';
	exit;
}

$app = require_once __DIR__ . '/app.php';

$app->boot();

$boot = $app['boot_count']->get('sync');

echo 'sync started .. ' . $boot . "\n";

$loop_count = 1;

while (true)
{
	sleep(1);

// 1.sync eLAND db to eLAS db

	if (false /* test for new events */)
	{

// 2.migrate eLAS db to eLAND db

	}
	else
	{
		$sync = $app['cache']->get('migrate_from_elas');

		if (count($sync))
		{


		}
		else
		{

		}
	}

//
	if ($loop_count % 1000 === 0)
	{
		error_log('..sync.. ' . $boot['count'] . ' .. ' . $loop_count);
	}

	$loop_count++;
}
