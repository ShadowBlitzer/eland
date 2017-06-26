<?php

if (php_sapi_name() !== 'cli')
{
	echo '-- cli only --';
	exit;
}

$app = require_once __DIR__ . '/app.php';

$app->boot();

$boot = $app['boot_count']->get('mail');

error_log('... mail service started ... ' . $boot);

$loop_count = 1;

while (true)
{
	sleep(2);

	if ($loop_count % 1800 === 0)
	{
		error_log('..mail.. ' . $boot . ' .. ' . $loop_count);
	}

	$loop_count++;

	$record = $app['queue']->get(['mail']);

	if (!count($record) or !$record)
	{
		continue;
	}

	$app['mail']->process($record['data']);
}
