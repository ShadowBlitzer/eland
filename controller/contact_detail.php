<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class contact_detail
{
	public function index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('contact_detail/a_index.html.twig', []);
	}
}

