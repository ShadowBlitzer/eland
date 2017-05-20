<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class users
{
	public function index(Request $request, app $app, string $type)
	{
		return $app['twig']->render('page/terms.html.twig', []);
	}

	public function admin_index(Request $request, app $app, string $type)
	{

	}





}

