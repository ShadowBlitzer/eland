<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class account
{
	public function index(Request $request, app $app, string $schema, string $access, string $account_type)
	{
		return $app['twig']->render('account/' . $access . '_index.html.twig', []);
	}

	public function map(Request $request, app $app, string $schema, string $access, string $account_type)
	{
		return $app['twig']->render('account/' . $access . '_map.html.twig', []);
	}

	public function tile(Request $request, app $app, string $schema, string $access, string $account_type)
	{
		return $app['twig']->render('account/' . $access . '_tile.html.twig', []);
	}

	public function show(Request $request, app $app, string $schema, string $access, string $account_type)
	{
		return $app['twig']->render('account/' . $access . '_show.html.twig', []);
	}


}

