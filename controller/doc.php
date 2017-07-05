<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class doc
{
	public function index(Request $request, app $app, string $schema, string $access)
	{


		return $app['twig']->render('doc/' . $access . '_index.html.twig', []);
	}

	public function map(Request $request, app $app, string $schema, string $access, array $doc)
	{


		return $app['twig']->render('doc/' . $access . '_index.html.twig', []);
	}

	public function show(Request $request, app $app, string $schema, string $access, array $forum)
	{
		return $app['twig']->render('doc/' . $access . '_show.html.twig', [
			'message'	=> $message,
		]);
	}


}

