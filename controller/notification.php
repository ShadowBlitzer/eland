<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class notification
{
	public function index(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('notification/' . $access . '_index.html.twig', []);
	}


	public function form_self(Request $request, app $app, string $schema, string $access)
	{

		return $app['twig']->render('notification/' . $access . '_show.html.twig', []);
	}

	public function show_self(Request $request, app $app, string $schema, string $access)
	{

		return $app['twig']->render('notification/' . $access . '_show_self.html.twig', []);
	}

	public function add(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('notification/' . $access . '_register.html.twig', []);
	}

}
