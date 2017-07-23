<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class permission
{
	public function index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('permission/a_index.html.twig', []);
	}
}

