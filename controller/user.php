<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class user
{
	public function index(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('user/' . $access . '_index.html.twig', []);
	}

	public function show(Request $request, app $app, string $schema, string $access, array $user)
	{
		return $app['twig']->render('user/' . $access . '_show.html.twig', []);
	}

	public function show_self(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('user/' . $access . '_show_self.html.twig', []);
	}
}

