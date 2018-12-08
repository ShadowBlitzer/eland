<?php
$rootpath = '../';
$page_access = 'admin';
require_once __DIR__ . '/../include/web.php';

$tschema = $app['this_group']->get_schema();

$except = $_GET['except'] ?? 0;
$schema = $_GET['schema'] ?? '';

if ($schema !== $tschema || !$schema)
{
	http_response_code(404);
	exit;
}

if (!ctype_digit((string) $except))
{
	http_response_code(404);
	exit;
}

$account_codes = [];

$st = $app['db']->prepare('select letscode
	from ' . $tschema . '.users
	where id <> ?
	order by letscode asc');

$st->bindValue(1, $except);
$st->execute();

while ($row = $st->fetch())
{
	if (empty($row['letscode']))
	{
		continue;
	}

	$account_codes[] = $row['letscode'];
}

$account_codes = json_encode($account_codes);

$params = [
	'schema'	=> $schema,
	'except'	=> $except,
];

$app['typeahead']->set_thumbprint('account_codes', $params, crc32($account_codes));

header('Content-type: application/json');

echo $account_codes;
