<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class mass_transaction
{
	public function form(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('mass_transaction/a_form.html.twig', []);
	}
}

