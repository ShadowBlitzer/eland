<?php

$page_access = 'guest';
require_once __DIR__ . '/include/web.php';

$logins = $app['session']->get('logins') ?? [];

foreach($logins as $sch => $uid)
{
	$app['xdb']->set('logout', $uid, ['time' => time()], $sch);
}

$app['session']->invalidate();

$app['monolog']->info('user logged out',
	['schema' => $app['tschema']]);

header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Location: ' . $rootpath . 'login.php');
exit;
