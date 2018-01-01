<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ConfigController extends AbstractController
{
	public function index(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_index.html.twig', []);
	}

	public function balance_limits(Request $request, string $schema, string $access)
	{



		
		return $this->render('config/a_balance_limits.html.twig', [

		]);
	}

	public function ads(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_ads.html.twig', [

		]);
	}

	public function naming(Request $request, string $schema, string $access)
	{




		return $this->render('config/a_naming.html.twig', [

		]);
	}

	public function mail_addresses(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_mail_addresses.html.twig', [

		]);
	}

	public function periodic_mail(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_periodic_mail.html.twig', [

		]);
	}

	public function contact_form(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_contact_form.html.twig', [

		]);
	}

	public function registration_form(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_registration_form.html.twig', [

		]);
	}

	public function forum(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_forum.html.twig', [

		]);
	}

	public function members(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_members.html.twig', [

		]);
	}

	public function system(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_system.html.twig', [

		]);
	}
}
