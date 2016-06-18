<?php
$rootpath = '../';
$page_access = 'guest';
require_once $rootpath . 'includes/inc_default.php';

$group_id = isset($_GET['group_id']) ? $_GET['group_id'] : 'self';
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

if ($s_guest && $status != 'active')
{
	http_response_code(403);
	exit;
}

if(!$s_admin && !in_array($status, ['active', 'extern']))
{
	http_response_code(403);
	exit;
}

if ($group_id == 'self')
{
	switch($status)
	{
		case 'extern':
			$status_sql = '= 7';
			break;
		case 'inactive':
			$status_sql = '= 0';
			break;
		case 'ip':
			$status_sql = '= 5';
			break;
		case 'im':
			$status_sql = '= 6';
			break;
		case 'active':
			$status_sql = 'in (1, 2)';
			break;
		default:
			http_response_code(404);
			exit;
	}

	$users = users_to_json($schema, $status_sql);

	invalidate_typeahead_thumbprint('users_' . $status, false, crc32($users));

	header('Content-type: application/json');
	echo $users;
	exit;
}

$group = $db->fetchAssoc('SELECT * FROM letsgroups WHERE id = ?', array($group_id));

$group['domain'] = get_host($group);

if (!$group || $status != 'active')
{
	http_response_code(404);
	exit;
}

if ($group['apimethod'] != 'elassoap')
{
	header('Content-type: application/json');
	echo '{}';
	exit;
}

if (isset($schemas[$group['domain']]))
{
	$remote_schema = $schemas[$group['domain']];

	if ($db->fetchColumn('select id from ' . $remote_schema . '.letsgroups where url = ?', array($base_url)))
	{
		$active_users = users_to_json($remote_schema);

		invalidate_typeahead_thumbprint('users_active', $group['url'], crc32($active_users));

		header('Content-type: application/json');
		echo $active_users;
		exit;
	}

	http_response_code(403);
	exit;
}

$active_users = $redis->get($group['url'] . '_typeahead_data');

if ($active_users)
{
	invalidate_typeahead_thumbprint('users_active', $group['url'], crc32($active_users));

	header('Content-type: application/json');
	echo $active_users;
	exit;
}
else
{
	http_response_code(404);
	exit;
}


/*
 *
 */
function users_to_json($schema, $status_sql = 'in (1, 2)')
{
	global $db;

	$fetched_users = $db->fetchAll(
		'SELECT letscode as c,
			name as n,
			extract(epoch from adate) as a,
			status as s,
			postcode as p
		FROM ' . $schema . '.users
		WHERE status ' . $status_sql
	);

	$users = array();

	$new_user_days = readconfigfromdb('newuserdays', $schema);

	foreach ($fetched_users as $user)
	{
		$user['nd'] = $new_user_days;

		if ($user['s'] == 1)
		{
			unset($user['s']);
		}

		$users[] = $user;
	}

	return json_encode($users);
}

