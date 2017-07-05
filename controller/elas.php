<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class elas
{
	public function group_login(Request $request, app $app, string $schema, string $access, string $account)
	{
		return $app->json([]);
	}

	public function soap_status(Request $request, app $app, string $schema, string $access, string $account)
	{
		return $app->json([]);
	}
}

