<?php

use Symfony\Component\Finder\Finder;
use eland\util\queue_container;
use eland\util\task_container;

if (php_sapi_name() !== 'cli')
{
	echo '-- cli only --';
	exit;
}

require_once __DIR__ . '/include/worker.php';

echo "worker started\n";

$boot = $app['eland.cache']->get('boot');

if (!count($boot))
{
	$boot = ['count' => 0];
}

$boot['count']++;
$app['eland.cache']->set('boot', $boot);

$queue = new queue_container($app, 'queue');
$task = new task_container($app, 'task');
$schema_task = new task_container($app, 'schema_task');

$loop_count = 1;

while (true)
{
	$app['eland.log_db']->update();

	sleep(1);

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

	if ($loop_count % 1000 == 0)
	{
		error_log('..worker.. ' . $boot['count'] . ' .. ' . $loop_count);
	}

	$loop_count++;
}

