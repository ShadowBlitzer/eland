<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class config
{
	public function index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('config/a_index.html.twig', []);
	}
}

