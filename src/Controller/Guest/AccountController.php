<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class AccountController extends AbstractController
{
	/**
	 * @Route("/accounts/{account_type}",
	 * name="account_index",
	 * methods={"GET", "POST"})
	 */
	public function index(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/accounts/map/{account_type}",
	 * name="account_map",
	 * methods="GET")
	 */
	public function map(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_map.html.twig', []);
	}

	/**
	 * @Route("/accounts/tile/{account_type}",
	 * name="account_tile",
	 * methods="GET")
	 */
	public function tile(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_tile.html.twig', []);
	}

	/**
	 * @Route("/accounts/{account_type}/{id}",
	 * 	name="account_show",
	 * 	methods="GET")
	 */
	public function show(Request $request, string $schema, string $access, string $account_type):Response
	{
		return $this->render('account/' . $access . '_show.html.twig', []);
	}

	/**
	 * @Route("/accounts/self",
	 * 	name="account_self",
	 * 	methods="GET")
	 */
	public function show_self(Request $request, string $schema, string $access):Response
	{
		return $this->render('account/' . $access . '_show_self.html.twig', []);
	}
}
