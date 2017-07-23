<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class auto_min_limit
{
	public function form(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('auto_min_limit/a_form.html.twig', []);
	}
}

