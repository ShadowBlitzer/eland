<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ElasController extends Controller
{
	public function groupLogin(Request $request, string $schema, string $access, string $account)
	{
		return $this->json([]);
	}

	public function soapStatus(Request $request, string $schema, string $access, string $account)
	{
		return $this->json([]);
	}
}

