<?php

// development server

if (php_sapi_name() === 'cli-server')
{
	if (is_file(__DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI'])))
	{
    	return false;
	}
}

$app = require_once __DIR__ . '/../app.php';

$app->run();
