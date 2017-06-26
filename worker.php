<?php

use Symfony\Component\Finder\Finder;
use util\queue_container;
use util\task_container;

if (php_sapi_name() !== 'cli')
{
	echo '-- cli only --';
	exit;
}

$app = require_once __DIR__ . '/app.php';

$app->boot();

$boot = $app['boot_count']->get('worker');

echo 'worker started .. ' . $boot . "\n";

$queue = new queue_container($app, 'queue');
$task = new task_container($app, 'task');
$schema_task = new task_container($app, 'schema_task');

$loop_count = 1;

while (true)
{
	$app['log_db']->update();

	sleep(5);

	if ($queue->should_run())
	{
		$queue->run();
	}
	else if ($task->should_run())
	{
		$task->run();
	}
	else if ($schema_task->should_run())
	{
		$schema_task->run();
	}

	if ($loop_count % 1000 === 0)
	{
		error_log('..worker.. ' . $boot['count'] . ' .. ' . $loop_count);
	}

	if ($loop_count % 10 === 0)
	{
		$app['predis']->set('monitor_service_worker', '1');
		$app['predis']->expire('monitor_service_worker', 900);
	}

	$loop_count++;
}

