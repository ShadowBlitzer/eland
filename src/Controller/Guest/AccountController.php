<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class AccountController extends AbstractController
{
	/**
	 * @Route("/accounts/{account_type}", name="account_index")
	 * @Method({"GET", "POST"})
	 */
	public function index(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/accounts/{account_type}/map", name="account_map")
	 * @Method("GET")
	 */
	public function map(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_map.html.twig', []);
	}

	/**
	 * @Route("/accounts/{account_type}/tile", name="account_tile")
	 * @Method("GET")
	 */
	public function tile(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_tile.html.twig', []);
	}

	/**
	 * @Route("/accounts/{account_type}/{id}", name="account_show")
	 * @Method("GET")
	 */
	public function show(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_show.html.twig', []);
	}

	/**
	 * @Route("/accounts/self", name="account_self")
	 * @Method("GET")
	 */
	public function show_self(Request $request, string $schema, string $access):Response
	{
		return $this->render('account/' . $access . '_show_self.html.twig', []);
	}
}

