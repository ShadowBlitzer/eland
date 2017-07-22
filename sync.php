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

	if ($app['sync_to_elas']->should_run())
	{
		$app['sync_to_elas']->run();
	}
	else if ($app['migrate_from_elas']->should_run())
	{
		$app['migrate_from_elas']->run();
	}

	if ($loop_count % 1000 === 0)
	{
		error_log('..sync.. ' . $boot . ' .. ' . $loop_count);
	}

	$loop_count++;
}
