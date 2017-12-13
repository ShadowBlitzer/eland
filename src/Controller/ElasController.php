<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ElasController extends Controller
{
	public function groupLoginAction(Request $request, string $schema, string $access, string $account)
	{
		return $this->json([]);
	}

	public function soap_status(Request $request, string $schema, string $access, string $account)
	{
		return $this->json([]);
	}
}

