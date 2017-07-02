<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class user
{
	public function index(Request $request, app $app)
	{


		return $app['twig']->render('user/index.html.twig', []);
	}







}

