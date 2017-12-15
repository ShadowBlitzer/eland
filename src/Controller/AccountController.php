<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends AbstractController
{
	public function index(Request $request, string $schema, string $access, string $account_type)
	{
		return $this->render('account/' . $access . '_index.html.twig', []);
	}

	public function map(Request $request, string $schema, string $access, string $account_type)
	{
		return $this->render('account/' . $access . '_map.html.twig', []);
	}

	public function tile(Request $request, string $schema, string $access, string $account_type)
	{
		return $this->render('account/' . $access . '_tile.html.twig', []);
	}

	public function show(Request $request, string $schema, string $access, string $account_type)
	{
		return $this->render('account/' . $access . '_show.html.twig', []);
	}

	public function show_self(Request $request, string $schema, string $access)
	{
		return $this->render('account/' . $access . '_show_self.html.twig', []);
	}
}

