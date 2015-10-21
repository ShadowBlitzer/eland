<?php

$rootpath = '../';
$role = 'guest';
require_once $rootpath . 'includes/inc_default.php';

$days = ($_GET['days']) ?: 365;
$user_id = ($_GET['user_id']) ?: 0;

if (!$user_id)
{
	exit;
}

$user = readuser($user_id);

if (!$user)
{
	exit;
}

$balance = (int) $user['saldo'];

$begin_date = date('Y-m-d H:i:s', time() - (86400 * $days));
$end_date = date('Y-m-d H:i:s');

$query = 'SELECT t.amount, t.id_from, t.id_to, 
		t.real_from, t.real_to, t.date, t.description, 
		u.id, u.name, u.letscode, u.accountrole, u.status 
	FROM transactions t, users u
	WHERE (t.id_to = ? OR t.id_from = ?) 
		AND (u.id = t.id_to OR u.id = t.id_from) 
		AND u.id <> ? 
		AND t.date >= ? 
		AND t.date <= ? 
	ORDER BY t.date DESC';
$trans = $db->fetchAll($query, array($user_id, $user_id, $user_id, $begin_date, $end_date));

$begin_date = strtotime($begin_date);
$end_date = strtotime($end_date);

$transactions = $users = $_users = array();

foreach ($trans as $t)
{
	$date = strtotime($t['date']);
	$out = ($t['id_from'] == $user_id) ? true : false;
	$mul = ($out) ? 1 : -1;
	$balance += $t['amount'] * $mul;

	$name = $t['name'];
	$real = ($t['real_from']) ? $t['real_from'] : null;
	$real = ($t['real_to']) ? $t['real_to'] : null;
	if ($real)
	{
		list($name, $code) = explode('(', $real);
		$name = trim($name);
		$code = $t['letscode'] . ' ' . trim($code, ' ()\t\n\r\0\x0B');
	}
	else
	{
		$code = $t['letscode'];
	}

	$transactions[] = array(
		'amount' => (int) $t['amount'],
		'date' => $date,
		'userCode' => strip_tags($code),
		'desc' => strip_tags($t['description']),
		'out' => $out,
	);

	$_users[(string) $code] = array(
		'name' => strip_tags($name),
		'linkable' => ($real || $t['status'] == 0) ? 0 : 1,
		'id' => $t['id'],
	);

}

foreach ($_users as $code => $ary)
{
	$users[] = array_merge($ary, array(
		'code' => (string) $code,
		));
}
unset($_users);

$transactions = array_reverse($transactions);

echo json_encode(array(
	'user_id' => $user_id,
	'ticks' => ($days == 365) ? 12 : 4,
	'currency' => readconfigfromdb('currency'),
	'transactions' => $transactions,
	'users' => $users,
	'beginBalance' => $balance,
	'begin' => $begin_date,
	'end' => $end_date,
));


