<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class ad
{
	public function index(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('ad/' . $access . '_index.html.twig', []);
	}

	public function show_self(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('ad/' . $access . '_show_self.html.twig', []);
	}

	public function show(Request $request, app $app, string $schema, string $access, array $ad)
	{
		return $app['twig']->render('ad/' . $access . '_show.html.twig', [
			'ad'	=> $ad,
		]);
	}


}

