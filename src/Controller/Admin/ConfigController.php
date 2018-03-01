<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use App\Repository\ConfigRepository;

class ConfigController extends AbstractController
{
	/**
	 * @Route("/config", name="config_index")
	 * @Method("GET")
	 */
	public function index(Request $request, string $schema, string $access):Reponse
	{
		return $this->render('config/a_index.html.twig', []);
	}

	/**
	 * @Route("/config/balance-limits", name="config_balance_limits")
	 * @Method({"GET", "POST"})
	 */
	public function balance_limits(Request $request, string $schema, string $access)
	{	
		return $this->render('config/a_balance_limits.html.twig', [

		]);
	}

	/**
	 * @Route("/config/ads", name="config_ads")
	 * @Method({"GET", "POST"})
	 */
	public function ads(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_ads.html.twig', [

		]);
	}

	/**
	 * @Route("/config/naming", name="config_naming")
	 * @Method({"GET", "POST"})
	 */
	public function naming(Request $request, string $schema, string $access)
	{

		return $this->render('config/a_naming.html.twig', [

		]);
	}

	/**
	 * @Route("/config/mail-addresses", name="config_mail_addresses")
	 * @Method({"GET", "POST"})
	 */
	public function mail_addresses(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_mail_addresses.html.twig', [

		]);
	}

	/**
	 * @Route("/config/periodic-mail", name="config_periodic_mail")
	 * @Method({"GET", "POST"})
	 */
	public function periodic_mail(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_periodic_mail.html.twig', [

		]);
	}

	/**
	 * @Route("/config/contact-form", name="config_contact_form")
	 * @Method({"GET", "POST"})
	 */
	public function contact_form(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_contact_form.html.twig', [

		]);
	}

	/**
	 * @Route("/config/registration-form", name="config_registration_form")
	 * @Method({"GET", "POST"})
	 */
	public function registration_form(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_registration_form.html.twig', [

		]);
	}

	/**
	 * @Route("/config/forum", name="config_forum")
	 * @Method({"GET", "POST"})
	 */
	public function forum(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_forum.html.twig', [

		]);
	}

	/**
	 * @Route("/config/members", name="config_members")
	 * @Method({"GET", "POST"})
	 */
	public function members(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_members.html.twig', [

		]);
	}

	/**
	 * @Route("/config/system", name="config_system")
	 * @Method({"GET", "POST"})
	 */
	public function system(Request $request, string $schema, string $access)
	{
		return $this->render('config/a_system.html.twig', [

		]);
	}
}
