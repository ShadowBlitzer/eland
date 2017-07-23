<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class custom_field
{
	public function index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('custom_field/a_index.html.twig', []);
	}
}

