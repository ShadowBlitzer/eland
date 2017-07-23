<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class periodic_charge
{
	public function form(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('periodic_charge/a_form.html.twig', []);
	}
}

