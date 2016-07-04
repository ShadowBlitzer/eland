<?php
$rootpath = './';
$page_access = 'admin';
require_once $rootpath . 'includes/inc_default.php';

if ($s_id != 'master')
{
	redirect_index();
}

$type = $_GET['type'] ?: false;

$r = "<br>\r\n";
header('Content-Type:text/html');
echo '*** migrate eLAND (temporal script) ***' . $r;

if (!$type)
{
	echo 'Error: Set a type.';
	exit;
}

/*
                            Table "eland_extra.events"
   Column    |            Type             |              Modifiers               
-------------+-----------------------------+--------------------------------------
 ts          | timestamp without time zone | default timezone('utc'::text, now())
 user_id     | integer                     | default 0
 user_schema | character varying(60)       | 
 agg_id      | character varying(255)      | not null
 agg_type    | character varying(60)       | 
 agg_version | integer                     | not null
 data        | jsonb                       | 
 event_time  | timestamp without time zone | default timezone('utc'::text, now())
 ip          | character varying(60)       | 
 event       | character varying(128)      | 
 agg_schema  | character varying(60)       | 
 eland_id    | character varying(40)       | 
Indexes:
    "events_pkey" PRIMARY KEY, btree (agg_id, agg_version)

                             Table "eland_extra.aggs"
   Column    |            Type             |              Modifiers               
-------------+-----------------------------+--------------------------------------
 agg_id      | character varying(255)      | not null
 agg_version | integer                     | not null
 data        | jsonb                       | 
 user_id     | integer                     | default 0
 user_schema | character varying(60)       | default ''::character varying
 ts          | timestamp without time zone | default timezone('utc'::text, now())
 agg_type    | character varying(60)       | not null
 agg_schema  | character varying(60)       | not null
 ip          | character varying(60)       | 
 event       | character varying(128)      | 
 eland_id    | character varying(40)       | 
Indexes:
    "aggs_pkey" PRIMARY KEY, btree (agg_id)
    "aggs_agg_schema_idx" btree (agg_schema)
    "aggs_agg_type_agg_schema_idx" btree (agg_type, agg_schema)
    "aggs_agg_type_idx" btree (agg_type)

	$rows = $db->executeQuery('select e1.agg_id,
		e1.agg_version,
		e1.data
		from eland_extra.events e1
		where e1.agg_version = (select max(e2.agg_version)
				from eland_extra.events e2
				where e1.agg_id = e2.agg_id)
			and e1.agg_type = \'setting\'
			and e1.agg_id in (?)',
			[$setting_agg_ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
*/

$mdb->connect();
$mclient = $mdb->get_client();

if ($type == 'user_fullname_access')
{
	$agg_id_ary = [];
	$fullname_access_ary = [];

	foreach ($schemas as $s)
	{
		$users_collection = $s . '_users';

		$users = $mclient->$users_collection->find();

		foreach ($users as $u)
		{
			$agg_id_ary[] = $s . '_user_fullname_access_' . $u['id'];
			$fullname_access_ary[$s][$u['id']] = $access_control->get_role($u['fullname_access']);
		}
	}

	$stored_ary = $exdb->get_many(['agg_type' => 'user_fullname_access', 'agg_id_ary' => $agg_id_ary]);

	foreach ($fullname_access_ary as $s => $user_fullname_access_ary)
	{
		foreach ($user_fullname_access_ary as $user_id => $fullname_access)
		{
			echo $s . ' -- ';
			echo link_user($user_id, $s, false, true);
			echo ' fullname visibility: ';
			echo $fullname_access;

			$agg_id = $s . '_user_fullname_access_' . $user_id;

			if (isset($stored_ary[$agg_id]))
			{
				echo ' (version: ' . $stored_ary[$agg_id]['agg_version'] . ') ';
			}

			if (!isset($stored_ary[$agg_id])
				|| $fullname_access != $stored_ary[$agg_id]['data']['fullname_access'])
			{
				$err = $exdb->set('user_fullname_access', $user_id, ['fullname_access' => $fullname_access], $s);

				if ($err)
				{
					echo $err;
				}
				else
				{
					echo ' UPDATED';
				}
			}

			echo $r;
		}
	}

	echo '--- end ---' . $r;
	exit;
}

if ($type == 'setting')
{
	$agg_id_ary = [];
	$setting_ary = [];

	foreach ($schemas as $s)
	{
		$settings_collection = $s . '_settings';

		$settings = $mclient->$settings_collection->find();

		foreach ($settings as $setting)
		{


			$agg_id_ary[] = $s . '_setting_' . $setting['name'];
			unset($setting['_id']);
			$setting_ary[$s][$setting['name']] = $setting;
		}
	}

	$stored_ary = $exdb->get_many(['agg_type' => 'setting', 'agg_id_ary' => $agg_id_ary]);

	foreach ($setting_ary as $s => $schema_settings)
	{
		foreach ($schema_settings as $setting_id => $value)
		{
			echo $s . ' -- ';
			echo ' setting: ' . $setting_id;
			echo ': ';
			echo $value;

			$agg_id = $s . '_setting_' . $setting_id;

			if ($stored_ary[$agg_id])
			{
				echo ' (version: ' . $stored_ary[$agg_id]['agg_version'] . ') ';
			}

			if (!$stored_ary[$agg_id]
				|| $setting != $stored_ary[$agg_id]['setting'])
			{
				$err = $exdb->set('setting', $setting_id, ['value' => $value], $s);

				if ($err)
				{
					echo $err;
				}
				else
				{
					echo ' UPDATED';
				}
			}

			echo $r . $r;
		}
	}

	echo '--- end ---' . $r;
	exit;
}

if ($type == 'forum')
{
	$forum_agg_ids = [];
	$forum_ary = [];

	foreach ($schemas as $s)
	{
		$forum_collection = $s . '_forum';

		$forum_posts = $mclient->$forum_collection->find();

		foreach ($forum_posts as $forum_post)
		{
			$p = $forum_post['_id']->__toString();
			$forum_agg_ids[] = $s . '_forum_' . $p;
			$forum_post_data = $forum_post;
			unset($forum_post_data['_id']);
			$forum_post_data['id'] = $p;
			if (isset($forum_post_data['access']))
			{
				$forum_post_data['access'] = $access_control->get_role($forum_post_data['access']);
			}
			$forum_ary[$s][$p] = $forum_post_data;
		}
	}

	$stored_ary = [];

	$rows = $db->executeQuery('select e1.agg_id,
		e1.agg_version,
		e1.data
		from eland_extra.events e1
		where e1.agg_version = (select max(e2.agg_version)
				from eland_extra.events e2
				where e1.agg_id = e2.agg_id)
			and e1.agg_type = \'forum\'
			and e1.agg_id in (?)',
			[$forum_agg_ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

	foreach ($rows as $row)
	{
		$forum_post = json_decode($row['data'], true);

		$stored_ary[$row['agg_id']] = [
			'agg_version'			=> $row['agg_version'],
			'forum_post'			=> $forum_post,
		];
	}

	foreach ($forum_ary as $s => $forum_post_ary)
	{
		foreach ($forum_post_ary as $id => $forum_post)
		{
			echo $s . ' -- ';
			echo ' forum_post: ' . $id;
			echo ': ';
			echo json_encode($forum_post);

			$agg_id = $s . '_forum_' . $id;

			if ($stored_ary[$agg_id])
			{
				echo ' (version: ' . $stored_ary[$agg_id]['agg_version'] . ') ';
			}

			if (!$stored_ary[$agg_id]
				|| $forum_post != $stored_ary[$agg_id]['forum_post'])
			{
				$agg_version = (isset($stored_ary[$agg_id]['agg_version'])) ? $stored_ary[$agg_id]['agg_version'] + 1 : 1;

				$db->insert('eland_extra.events', [
					'agg_id'		=> $agg_id,
					'agg_type'		=> 'forum',
					'agg_version'	=> $agg_version,
					'data'			=> json_encode($forum_post),
					'event'			=> 'forum_updated'
				]);

				echo ' UPDATED';
			}

			echo $r . $r;
		}
	}

	echo '--- end ---' . $r;
	exit;
}
