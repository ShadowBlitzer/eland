<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class message
{
	public function index(Request $request, app $app, string $schema, string $access)
	{


		return $app['twig']->render('message/' . $access . '_index.html.twig', []);
	}

	public function show(Request $request, app $app, string $schema, string $access, array $message)
	{
		return $app['twig']->render('message/' . $access . '_show.html.twig', [
			'message'	=> $message,
		]);
	}


}

