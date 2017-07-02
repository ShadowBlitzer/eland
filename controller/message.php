<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class message
{
	public function g_index(Request $request, app $app, string $schema)
	{


		return $app['twig']->render('message/g_index.html.twig', []);
	}

	public function i_index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('message/i_index.html.twig', []);
	}

	public function u_index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('message/u_index.html.twig', []);
	}

	public function a_index(Request $request, app $app, string $schema)
	{


		return $app['twig']->render('message/a_index.html.twig', []);
	}

	public function g_show(Request $request, app $app, string $schema, array $message)
	{
		return $app['twig']->render('message/g_show.html.twig', [
			'message'	=> $message,
		]);
	}

	public function i_show(Request $request, app $app, string $schema, array $message)
	{
		return $app['twig']->render('message/i_show.html.twig', [
			'message'	=> $message,
		]);
	}

	public function u_show(Request $request, app $app, string $schema, array $message)
	{
		return $app['twig']->render('message/u_show.html.twig', [
			'message'	=> $message,
		]);
	}

	public function a_show(Request $request, app $app, string $schema, array $message)
	{
		return $app['twig']->render('message/a_show.html.twig', [
			'message'	=> $message,
		]);
	}
}

