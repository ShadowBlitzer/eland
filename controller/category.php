<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class category
{
	public function index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('category/a_index.html.twig', []);
	}
}

