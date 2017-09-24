<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class user_typeahead
{
	public function get(Request $request, app $app, 
		string $schema, string $access, string $user_type)
	{






		
//		return $app['twig']->render('user/' . $access . '_index.html.twig', []);
	}

	public function get_interlets(Request $request, app $app, 
		string $schema, string $access, int $user)
	{
		return $app['twig']->render('user/' . $access . '_show.html.twig', []);
	}
}

