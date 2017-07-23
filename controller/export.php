<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class export
{
	public function index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('export/a_index.html.twig', []);
	}
}

