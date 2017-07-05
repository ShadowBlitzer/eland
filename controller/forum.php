<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class forum
{
	public function index(Request $request, app $app, string $schema, string $access)
	{


		return $app['twig']->render('forum/' . $access . '_index.html.twig', []);
	}

	public function map(Request $request, app $app, string $schema, string $access, array $forum)
	{


		return $app['twig']->render('forum/' . $access . '_index.html.twig', []);
	}

	public function show(Request $request, app $app, string $schema, string $access, array $forum)
	{
		return $app['twig']->render('forum/' . $access . '_show.html.twig', [
			'message'	=> $message,
		]);
	}


}

