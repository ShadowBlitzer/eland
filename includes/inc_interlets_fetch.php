<?php

/*
 *
 */
function fetch_interlets_msgs($client, $url)
{
	global $redis, $r;

	$msgs = array();

	$crawler = $client->request('GET', $url . '/renderindex.php');

	echo $url . $r;

	$msgs_table = $crawler->filter('table')
		->last()
		->filter('tr')
		->first()
		->nextAll()
		->each(function ($node) use (&$msgs, $r)
	{
		$first_td = $node->filter('td')->first();
		$va = $first_td->text();
		$next_tds = $first_td->siblings();
		$a = $next_tds->eq(0)->filter('a');
		$del = $a->filter('del')->count();

		if (!$del)
		{
			$href = $a->attr('href');
			$content = $a->text();
			$user = $next_tds->eq(1)->text();

			list($dummy, $msgid) = explode('=', $href);

			$redis_msg_key = $url . '_interlets_msg_' . $msgid;

			echo '_' . $va . "_  id:" . $msgid . $r;
			echo $content . $r . $user;
			echo $r . $r;

		}

	});
}

/*
 *
 */
function fetch_interlets_typeahead_data($client, $url)
{
	global $redis, $r;

	$crawler = $client->request('GET', $url . '/rendermembers.php');

	echo $url;

	$users = array();

	$h = $crawler->filter('table tr')
		->first()
		->nextAll()
		->each(function ($node) use (&$users)
	{
		$user = array();

		$td = $node->filter('td')->first();
		$bgcolor = $td->attr('bgcolor');
		$postcode = $td->siblings()->eq(3)->text();

		$user['c'] = $td->text();
		$user['n'] = $td->nextAll()->text();

		if ($bgcolor)
		{
			$user['s'] = (strtolower(substr($bgcolor, 1, 1)) > 'c') ? 2 : 3;
		}

		if ($postcode)
		{
			$user['p'] = $postcode;
		} 

		$users[] = $user;
	}); 

	$redis_data_key = $url . '_typeahead_data';
	$data_string = json_encode($users);

	if ($data_string != $redis->get($redis_data_key))
	{
		$redis_thumbprint_key = $url . '_typeahead_thumbprint';
		$redis->set($redis_thumbprint_key, time());
		$redis->expire($redis_thumbprint_key, 5184000);	// 60 days
		$redis->set($redis_data_key, $data_string);
	}
	$redis->expire($redis_data_key, 86400);		// 1 day

	$redis_refresh_key = $url . '_typeahead_updated';
	$redis->set($redis_refresh_key, '1');
	$redis->expire($redis_refresh_key, 43200);		// 12 hours

	$user_count = count($users);

	$redis_user_count_key = $url . '_active_user_count';
	$redis->set($redis_user_count_key, $user_count);
	$redis->expire($redis_user_count_key, 86400); // 1 day

	log_event('', 'Cron', 'typeahead data fetched of ' . $user_count . ' users from group ' . $url);

	echo '----------------------------------------------------' . $r;
	echo $redis_data_key . $r;
	echo $redis_refresh_key . $r;
	echo 'user count: ' . $user_count . $r;	
}