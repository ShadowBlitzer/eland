<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class status
{
	public function index(Request $request, app $app, string $schema, string $access)
	{










		return $app['twig']->render('status/a_index.html.twig', []);
	}
}

