<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class account
{
	public function g_index(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/g_index.html.twig', []);
	}

	public function i_index(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/i_index.html.twig', []);
	}

	public function u_index(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/u_index.html.twig', []);
	}

	public function a_index(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/a_index.html.twig', []);
	}

	public function g_show(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/g_show.html.twig', []);
	}

	public function i_show(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/i_show.html.twig', []);
	}

	public function u_show(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/u_show.html.twig', []);
	}

	public function a_show(Request $request, app $app, string $schema, string $account_type)
	{
		return $app['twig']->render('account/a_show.html.twig', []);
	}


}

