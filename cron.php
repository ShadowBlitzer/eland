<?php

$r = "<br>\r\n";
$now = gmdate('Y-m-d H:i:s');

$php_sapi_name = php_sapi_name();

if ($php_sapi_name == 'cli')
{
	echo 'The cron should not run from the cli but from the http web server.' . $r;
	exit;
}

defined('__DIR__') or define('__DIR__', dirname(__FILE__));
chdir(__DIR__);

$page_access = 'anonymous';

require_once __DIR__ . '/includes/inc_default.php';

require_once __DIR__ . '/includes/inc_saldo_mail.php';

header('Content-Type:text/html');
echo '*** Cron eLAND ***' . $r;

echo 'php_sapi_name: ' . $php_sapi_name . $r;
echo 'php version: ' . phpversion() . $r;

$app['eland.log_db']->update();

/** take cron task from the queue and process **/

/*
if ($app['redis']->get('process_queue_sleep') && $app['eland.queue']->count())
{
	$queue = $app['eland.queue']->get('', 5);

	foreach ($queue as $q_msg)
	{
		$topic = $q_msg['topic'];
		$data = $q_msg['data'];

		if (!isset($data['schema']))
		{
			$app['monolog']->error('no schema set for queue msg id : ' . $q_msg['id'] . ' data: ' .
				json_encode($data) . ' topic: ' . $topic);

			continue;
		}

		switch ($q_msg['topic'])
		{
			case 'mail':
				
				break;
			case 'autominlimit':

				break;
			default:
				$app['monolog']->error('Task not recognised: ' . json_encode($q_msg));
				break;
		}
	}

	$app['redis']->set('process_queue_sleep', '1');
	$app['redis']->expire('process_queue_sleep', 50);
	echo '-- Cron end. --';
	exit;	
}



*/

/*
 *  select in which schema to perform updates
 */

$schema_lastrun_ary = [];

foreach ($app['eland.groups']->get_schemas() as $ho => $sch)
{
	$lastrun = $app['db']->fetchColumn('select max(lastrun) from ' . $sch . '.cron');
	$schema_lastrun_ary[$sch] = ($lastrun) ?: 0;
}

unset($sch, $ho, $selected);

if ($app['eland.groups']->count())
{
	asort($schema_lastrun_ary);

	echo 'Schema (domain): last cron timestamp : interletsqueue timestamp' . $r;
	echo '---------------------------------------------------------------' . $r;

	foreach ($schema_lastrun_ary as $sch => $time)
	{
		echo $sch . ' (' . $app['eland.groups']->get_host($sch) . '): ' . $time;

		if (!isset($selected))
		{
			$app['eland.this_group']->force($sch);
			echo ' (selected)';
			$selected = true;
		}

		echo $r;
	}
}
else
{
	echo '-- No installed domains found. --' . $r;
	exit;
}

$newusertreshold = time() - readconfigfromdb('newuserdays') * 86400;

echo '*** Cron system running [' . $app['eland.this_group']->get_schema() . '] ***' . $r;

$app['eland.base_url'] = $app['eland.protocol'] . $app['eland.this_group']->get_host();


/**
 * typeahead && msgs from eLAS interlets update
 */

$update_msgs = false;

$groups = $app['db']->fetchAll('select *
	from letsgroups
	where apimethod = \'elassoap\'
		and remoteapikey IS NOT NULL
		and url <> \'\'');

foreach ($groups as $group)
{
	$group['domain'] = strtolower(parse_url($group['url'], PHP_URL_HOST));

	if ($app['eland.groups']->get_schema($group['domain']))
	{
		unset($group);
		continue;
	}

	if ($app['redis']->get($app['eland.this_group']->get_schema() . '_token_failed_' . $group['remoteapikey'])
		|| $app['redis']->get($app['eland.this_group']->get_schema() . '_connection_failed_' . $group['domain']))
	{
		unset($group);
		continue;
	}

	if (!$app['redis']->get($group['domain'] . '_typeahead_updated'))
	{
		break;
	}
/*
	if (!$app['redis']->get($group['domain'] . '_msgs_updated'))
	{
		$update_msgs = true;
		break;
	}
*/
	unset($group);
}

if (isset($group))
{
	$err_group = $group['groupname'] . ': ';

	$soapurl = ($group['elassoapurl']) ? $group['elassoapurl'] : $group['url'] . '/soap';
	$soapurl = $soapurl . '/wsdlelas.php?wsdl';
	$apikey = $group['remoteapikey'];
	$client = new nusoap_client($soapurl, true);
	$err = $client->getError();

	if ($err)
	{

		echo $err_group . 'Can not get connection.' . $r;
		$redis_key = $app['eland.this_group']->get_schema() . '_connection_failed_' . $group['domain'];
		$app['redis']->set($redis_key, '1');
		$app['redis']->expire($redis_key, 21600);  // 6 hours

	}
	else
	{

		$token = $client->call('gettoken', ['apikey' => $apikey]);
		$err = $client->getError();

		if ($err)
		{
			echo $err_group . 'Can not get token.' . $r;
		}
		else if (!$token || $token == '---')
		{
			$err = 'invalid token';
			echo $err_group . 'Invalid token.' . $r;
		}

		if ($err)
		{
			$redis_key = $app['eland.this_group']->get_schema() . '_token_failed_' . $group['remoteapikey'];
			$app['redis']->set($redis_key, '1');
			$app['redis']->expire($redis_key, 21600);  // 6 hours
		}
	}

	if (!$err)
	{
		try
		{
			$client = new Goutte\Client();

			$crawler = $client->request('GET', $group['url'] . '/login.php?token=' . $token);

			require_once __DIR__ . '/includes/inc_interlets_fetch.php';

			if ($update_msgs)
			{
				echo 'fetch interlets messages' . $r;
				fetch_interlets_msgs($client, $group);
			}
			else
			{
				echo 'fetch interlets typeahead data' . $r;
				fetch_interlets_typeahead_data($client, $group);
			}

			echo '----------------------------------------------------' . $r;
			echo 'end Cron ' . "\n";
			exit;
		}
		catch (Exception $e)
		{
			$err = $e->getMessage();
			echo $err . $r;
			$redis_key = $app['eland.this_group']->get_schema() . '_token_failed_' . $group['remoteapikey'];
			$app['redis']->set($redis_key, '1');
			$app['redis']->expire($redis_key, 21600);  // 6 hours

		}
	}

	if ($err)
	{
		echo '-- retry after 6 hours --' . $r;
		echo '-- continue --' . $r;
	}
}
else
{
	echo '-- no interlets data fetch needed -- ' . $r;
}

/*
 * Process autominlimits
 */

$autominlimit_queue = $app['eland.queue']->get('autominlimit', 6);

if (count($autominlimit_queue))
{
	echo '-- processing autominlimit queue -- ' . $r;

	foreach ($autominlimit_queue as $q)
	{
		$app['eland.task.autominlimit']->process($q['data']);
	}

	echo '--- end queue autominlimit --- ' . $r;
}
else
{
	echo '-- autominlimit queue is empty --' . $r;
}

/** send mail **/

$mail_queue = $app['eland.queue']->get('mail', 6);

if (count($mail_queue))
{
	echo '-- processing mail queue -- ' . $r;

	foreach ($mail_queue as $q)
	{
		$app['eland.task.mail']->process($q['data']);
	}

	echo '--- end mail queue --- ' . $r;
}
else
{
	echo '-- mail queue is empty --' . $r;
}


$geo_queue = $app['eland.queue']->get('geocode', 6);

if (count($geo_queue))
{
	echo '-- processing geo queue -- ' . $r;

	foreach ($geo_queue as $q)
	{
		$app['eland.task.geocode']->process($q['data']);
	}

	echo '-- end geo queue -- ' . $r;
}
else
{
	echo '-- geo queue is empty -- ' . $r;
}


run_cronjob('geo_queue', 7200);

function geo_queue()
{
	global $app;

	$log_ary = [];

	$st = $app['db']->prepare('select c.value, c.id_user
		from contact c, type_contact tc, users u
		where c.id_type_contact = tc.id
			and tc.abbrev = \'adr\'
			and c.id_user = u.id
			and u.status in (1, 2)');

	$st->execute();

	while (($row = $st->fetch()) && count($log_ary) < 20)
	{
		$data = [
			'adr'		=> trim($row['value']),
			'uid'		=> $row['id_user'],
			'schema'	=> $app['eland.this_group']->get_schema(),
		];

		if ($app['eland.task.geocode']->queue($data) !== false)
		{
			$log_ary[] = link_user($row['id_user'], false, false, true) . ': ' . $data['adr'];
		}
	}

	if (count($log_ary))
	{
		$app['monolog']->info('Adresses queued for geocoding: ' . implode(', ', $log_ary));
	}
}


/**
 * Periodic overview mail
 */

run_cronjob('saldo', 86400 * readconfigfromdb('saldofreqdays'));

/**
 * Report expired messages to the admin by mail
 */

run_cronjob('admin_exp_msg', 86400 * readconfigfromdb('adminmsgexpfreqdays'), readconfigfromdb('adminmsgexp'));

function admin_exp_msg()
{
	global $app, $now, $r;

	$query = 'SELECT m.id_user, m.content, m.id, to_char(m.validity, \'YYYY-MM-DD\') as vali
		FROM messages m, users u
		WHERE u.status <> 0
			AND m.id_user = u.id
			AND validity <= ?';
	$messages = $app['db']->fetchAll($query, [$now]);

	if (empty($to))
	{
		echo 'No admin E-mail address specified in config' . $r;
		return false;
	}

	$subject = 'Rapport vervallen Vraag en aanbod';

	$text = "-- Dit is een automatische mail, niet beantwoorden aub --\n\n";
	$text .= "Gebruiker\t\tVervallen vraag of aanbod\t\tVervallen\n\n";
	
	foreach($messages as $key => $value)
	{
		$text .= link_user($value['id_user'], false, false) . "\t\t" . $value['content'] . "\t\t" . $value['vali'] ."\n";
		$text .= $app['eland.base_url'] . '/messages.php?id=' . $value['id'] . " \n\n";
	}

	$app['eland.task.mail']->queue(['to' => 'admin', 'subject' => $subject, 'text' => $text]);

	return true;
}

/**
 * Notify users of expired messages
 */

run_cronjob('user_exp_msgs', 86400, readconfigfromdb('msgexpwarnenabled'));

function user_exp_msgs()
{
	global $app, $now;

	//Fetch a list of all non-expired messages that havent sent a notification out yet and mail the user
	$msgcleanupdays = readconfigfromdb('msgexpcleanupdays');
	$warn_messages  = $app['db']->fetchAll('SELECT m.*
		FROM messages m
			WHERE m.exp_user_warn = \'f\'
				AND m.validity < ?', [$now]);

	foreach ($warn_messages AS $key => $value)
	{
		//For each of these, we need to fetch the user's mailaddress and send her/him a mail.
		echo 'Found new expired message ' . $value['id'];
		$user = readuser($value['id_user']);

		$extend_url = $app['eland.base_url'] . '/messages.php?id=' . $value['id'] . '&extend=';
		$va = ($value['msg_type']) ? 'aanbod' : 'vraag';
		$text = "-- Dit is een automatische mail, niet beantwoorden aub --\r\n\r\n";
		$text .= "Beste " . $user['name'] . "\n\nJe " . $va . ' ' . $value['content'] . ' ';
		$text .= 'is vervallen en zal over ' . $msgcleanupdays . ' dagen verwijderd worden. ';
		$text .= 'Om dit te voorkomen kan je verlengen met behulp van één van de onderstaande links (Als ';
		$text .= 'je niet ingelogd bent, zal je eerst gevraagd worden in te loggen). ';
		$text .= "\n\n Verlengen met \n\n";
		$text .= "één maand: " . $extend_url . "30 \n";
		$text .= "twee maanden: " . $extend_url . "60 \n";
		$text .= "zes maanden: " . $extend_url . "180 \n";
		$text .= "één jaar: " . $extend_url . "365 \n";
		$text .= "twee jaar: " . $extend_url . "730 \n";
		$text .= "vijf jaar: " . $extend_url . "1825 \n\n";
		$text .= "Nieuw vraag of aanbod ingeven: " . $app['eland.base_url'] . "/messages.php?add=1 \n\n";
		$text .= "Als je nog vragen of problemen hebt, kan je mailen naar ";
		$text .= readconfigfromdb('support');

		$subject = 'Je ' . $va . ' is vervallen.';

		if (empty($from))
		{
			echo "Mail from address is not set in configuration\n";
			return;
		}

		$app['eland.task.mail']->queue(['to' => $value['id_user'], 'subject' => $subject, 'text' => $text]);
	}

	$app['db']->executeUpdate('update messages set exp_user_warn = \'t\' WHERE validity < ?', [$now]);

	//no double warn in eLAND.

	return true;
}

/**
 * Cleanup messages
 */

run_cronjob('cleanup_messages', 86400);

function cleanup_messages()
{
	global $app;

	$app['eland.task.cleanup_messages']->run();

	return true;
}

/**
 * Resyncronize balances
 */

run_cronjob('saldo_update', 86400); 

function saldo_update()
{
	global $app, $r;

	$app['eland.task.saldo_update']->run();

	return true;
}

/**
 * remove expired newsitems
 */

run_cronjob('cleanup_news', 86400);

function cleanup_news()
{
    global $app, $now;

	$news = $app['db']->fetchAll('select id from news where itemdate < ? and sticky = \'f\'', [$now]);

	foreach ($news as $n)
	{
		$app['eland.xdb']->del('news_access', $n['id']);
		$app['db']->delete('news', ['id' => $n['id']]);
	}

	return true;
}


/**
 * cleanup image files // schema-independent cronjob.
 * images are deleted from s3 170 days after been deleted from db.
 */

if (!$app['redis']->get('cron_cleanup_image_files'))
{
	echo '+++ cleanup image files +++' . $r;

	$marker = $app['redis']->get('cleanup_image_files_marker');

	$marker = ($marker) ? $marker : '0';

	$time_treshold = time() - (84600 * 170);

	$del_count = 0;

	$objects = $app['eland.s3']->img_list($marker);

	$reset = true;

	foreach ($objects as $k => $object)
	{
		if ($k > 4)
		{
			$reset = false;
			break;
		}

		$delete = $del_str = false;

		$object_time = strtotime($object['LastModified']);

		$old = ($object_time < $time_treshold) ? true : false;

		$str_log = $k . ' ' .  $object['Key'] . ' ' . $object['LastModified'] . ' ';

		$str_log .= ($old) ? 'OLD' : 'NEW';

		error_log($str_log);

		if (!$old)
		{
			continue;
		}

		list($sch, $type, $id, $hash) = explode('_', $object['Key']);

		if (ctype_digit((string) $sch))
		{
			error_log('-> elas import image. DELETE');
			$delete = true;
		} 


		if (!$delete && $app['eland.groups']->get_host($sch))
		{
			error_log('-> unknown schema');
			continue;
		}

		if (!$delete && !in_array($type, ['u', 'm']))
		{
			error_log('-> unknown type');
			continue;
		}

		if (!$delete && $type == 'u' && ctype_digit((string) $id))
		{
			$user = $app['db']->fetchAssoc('select id, "PictureFile" from ' . $sch . '.users where id = ?', [$id]);

			if (!$user)
			{
				$del_str = '->User does not exist.';
				$delete = true;
			}
			else if ($user['PictureFile'] != $object['Key'])
			{
				$del_str = '->does not match db key ' . $user['PictureFile'];
				$delete = true;
			}
		}

		if (!$delete && $type == 'm' && ctype_digit((string) $id))
		{
			$msgpict = $app['db']->fetchAssoc('select * from ' . $sch . '.msgpictures
				where msgid = ?
					and "PictureFile" = ?', [$id, $object['Key']]);

			if (!$msgpict)
			{
				$del_str = '->is not present in db.';
				$delete = true;
			}
		}

		if (!$delete)
		{
			continue;
		}

		$app['eland.s3']->img_del($object['Key']);

		if ($del_str)
		{
			$app['monolog']->info('(cron) image file ' . $object['Key'] . ' deleted ' . $del_str, ['schema' => $sch]);
		}

		$del_count++;
	}

	$app['redis']->set('cleanup_image_files_marker', $reset ? '0' : $object['Key']);
	$app['redis']->set('cron_cleanup_image_files', '1');
	$app['redis']->expire('cron_cleanup_image_files', 900);

	echo  $del_count . ' images deleted.' . $r;

	echo '+++ end image files cleanup +++' . $r . $r;
}
else
{
	echo '+++ cleanup image files not running +++' . $r;
}


/**
 * Cleanup old logs
 */

run_cronjob('cleanup_logs', 86400);

function cleanup_logs()
{
	global $app;

	$treshold = gmdate('Y-m-d H:i:s', time() - 86400 * 30);

	$app['db']->executeQuery('delete from eland_extra.logs
		where schema = ? and ts < ?', [$app['eland.this_group']->get_schema(), $treshold]);

	$app['monolog']->info('(cron) Cleaned up logs older than 30 days.');

	return true;
}

/**
 * Dummy cronjob in order to keep rotating groups for cronjobs.
 */

run_cronjob('cronschedule', 10);

function cronschedule()
{
	return true;
}

echo "*** Cron run finished ***" . $r;
exit;

////////////////////

function run_cronjob($name, $interval = 300, $enabled = null)
{
	global $app, $r, $now;
	static $lastrun_ary;

	if (!(isset($lastrun_ary) && is_array($lastrun_ary)))
	{
		$lastrun_ary = [];

		$rs = $app['db']->prepare('select cronjob, lastrun from cron');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$lastrun_ary[$row['cronjob']] = $row['lastrun'];
		}
	}

	$time = time();
	$lastrun = (isset($lastrun_ary[$name])) ? strtotime($lastrun_ary[$name] . ' UTC') : 0;

	if (!((($time - $interval) > $lastrun) & ($enabled || !isset($enabled))))
	{
		echo '+++ Cronjob: ' . $name . ' not running. +++' . $r;
		return;
	}

	echo '+++ Running ' . $name . ' +++' . $r;

	$updated = call_user_func($name);

	$lastrun = ((($time - ($lastrun + $interval)) > 86400) || ($interval < 86401)) ? $time : $lastrun + $interval;

	if (isset($lastrun_ary[$name]))
	{
		$app['db']->update('cron', ['lastrun' => gmdate('Y-m-d H:i:s', $lastrun)], ['cronjob' => $name]);
	}
	else
	{
		$app['db']->insert('cron', ['cronjob' => $name, 'lastrun'	=> $now]);
	}

	echo '+++ Cronjob ' . $name . ' finished. +++' . $r;

	return $updated;
}
