<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserTypeaheadController extends AbstractController
{
	/**
	 * @Route("/users/typeahead/{user_type}", name="user_typeahead", methods="GET")
	 */
	public function getLocal(Request $request,
		string $schema, string $access, string $user_type):Response
	{
		if ($access !== 'a' && !in_array($user_type, ['active', 'direct']))
		{
			return $this->json([], 403);
		}

		switch($user_type)
		{
			case 'post-active':
				$where_sql = '(status = 0 and adate is not null)
					or (status = 7 and accountrole in (\'user\', \'admin\'))';
				break;
			case 'pre-active':
				$where_sql = 'status in (5, 6) or (status = 0 and adate is null)';
				break;
			case 'active':
				$where_sql = 'status in (1, 2)
					or (status = 7 and accountrole = \'interlets\')';
				break;
			case 'direct':
				$where_sql = 'status in (1, 2)
					and accountrole in (\'user\', \'admin\')';
				break;
			case 'interlets':
				$where_sql = 'status in (1, 7)
					and accountrole = \'interlets\'';
				break;
			case 'all':
				$where_sql = '1 = 1';
				break;
			default:
				return $this->json([], 404);
				exit;
				break;
		}

		$fetched_users = $app['db']->fetchAll(
			'select letscode as c,
				name as n,
				extract(epoch from adate) as a,
				status,
				accountrole,
				postcode as p,
				saldo as b,
				minlimit as min,
				maxlimit as max
			from ' . $schema . '.users
			where ' . $where_sql . '
			order by letscode asc'
		);

		$users = [];

		foreach ($fetched_users as $user)
		{
			unset($t);

			switch ($user['status'])
			{
				case 0: case 7:
					$t = 'post-active';
					break;
				case 2:
					$t = 'leaving';
					break;
				case 5: case 6:
					$t = 'pre-active';
					break;
				default:
					break;
			}

			switch ($user['accountrole'])
			{
				case 'interlets':
					$t = 'interlets';
					break;
				default:
					break;
			}

			if (isset($t))
			{
				$user['t'] = $t;
			}

			unset($user['status'], $user['accountrole']);

			if ($user['max'] === 999999999)
			{
				unset($user['max']);
			}

			if ($user['min'] === -999999999)
			{
				unset($user['min']);
			}

			$user['a'] = +$user['a'];

			if (!$user['a'])
			{
				unset($user['a']);
			}

			$users[] = $user;
		}

		$out = json_encode($users);

		$app['thumbprint']->set($request->getPathInfo(), $out);

		$response = new Response($out);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	/**
	 * @Route("/users/interlets-typeahead/{id}", name="user_interlets_typeahead", methods="GET")
	 */
	public function getInterlets(Request $request,
		string $schema, string $access, int $user):Response
	{
		if (!in_array($access, ['a', 'u']))
		{
			return $this->json([], 403);
		}

		if (!$app['config']->get('interlets_en', $schema))
		{
			return $this->json([], 403);
		}

		if ($app['config']->get('template_lets', $schema))
		{
			return $this->json([], 403);
		}

		$user = $app['user_cache']->get($user);

		if (!$user)
		{
			return $this->json([], 404);
		}

		if ($user['accountrole'] !== 'interlets'
			|| !$user['letscode'])
		{
			return $this->json([], 404);
		}

		if ($user['interlets.schema'])
		{

		}

		$active_users = $app['cache']->get_string($group['domain'] . '_typeahead_data');

		if ($active_users)
		{
			$app['thumbprint']->invalidate_thumbprint('users_active', $group['domain'], crc32($active_users));

			header('Content-type: application/json');
			echo $active_users;
			exit;
		}
		else
		{
			http_response_code(404);
			exit;
		}

		return $this->render('user/' . $access . '_show.html.twig', []);
	}
}

/*


<?php
$rootpath = '../';
$page_access = 'guest';
require_once __DIR__ . '/../include/web.php';

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

	$users = users_to_json($app['this_group']->get_schema(), $status_sql);

	$app['typeahead']->invalidate_thumbprint('users_' . $status, false, crc32($users));

	header('Content-type: application/json');
	echo $users;
	exit;
}

$group = $app['db']->fetchAssoc('SELECT * FROM letsgroups WHERE id = ?', [$group_id]);

$group['domain'] = strtolower(parse_url($group['url'], PHP_URL_HOST));

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

if ($app['groups']->get_schema($group['domain']))
{
	$remote_schema = $app['groups']->get_schema($group['domain']);

	if ($app['db']->fetchColumn('select id from ' . $remote_schema . '.letsgroups where url = ?', [$app['base_url']]))
	{
		$active_users = users_to_json($remote_schema);

		$app['typeahead']->invalidate_thumbprint('users_active', $group['domain'], crc32($active_users));

		header('Content-type: application/json');
		echo $active_users;
		exit;
	}

	http_response_code(403);
	exit;
}

$active_users = $app['cache']->get_string($group['domain'] . '_typeahead_data');

if ($active_users)
{
	$app['typeahead']->invalidate_thumbprint('users_active', $group['domain'], crc32($active_users));

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

 /*
function users_to_json($sch, $status_sql = 'in (1, 2)')
{
	global $app;

	$fetched_users = $app['db']->fetchAll(
		'select letscode as c,
			name as n,
			extract(epoch from adate) as a,
			status as s,
			postcode as p,
			saldo as b,
			minlimit as min,
			maxlimit as max
		from ' . $schema . '.users
		where status ' . $status_sql
	);

	$users = [];

	foreach ($fetched_users as $user)
	{
		if ($user['s'] == 1)
		{
			unset($user['s']);
		}

		if ($user['max'] == 999999999)
		{
			unset($user['max']);
		}

		if ($user['min'] == -999999999)
		{
			unset($user['min']);
		}

		$users[] = $user;
	}

	return json_encode($users);
}

*/