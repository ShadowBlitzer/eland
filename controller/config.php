<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

class config
{
	public function index(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_index.html.twig', []);
	}

	public function balance_limits(Request $request, app $app, string $schema, string $access)
	{



		
		return $app['twig']->render('config/a_balance_limits.html.twig', [

		]);
	}

	public function ads(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_ads.html.twig', [

		]);
	}

	public function naming(Request $request, app $app, string $schema, string $access)
	{




		return $app['twig']->render('config/a_naming.html.twig', [

		]);
	}

	public function mail_addresses(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_mail_addresses.html.twig', [

		]);
	}

	public function periodic_mail(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_periodic_mail.html.twig', [

		]);
	}

	public function contact_form(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_contact_form.html.twig', [

		]);
	}

	public function registration_form(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_registration_form.html.twig', [

		]);
	}

	public function forum(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_forum.html.twig', [

		]);
	}

	public function members(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_members.html.twig', [

		]);
	}

	public function system(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('config/a_system.html.twig', [

		]);
	}
}
