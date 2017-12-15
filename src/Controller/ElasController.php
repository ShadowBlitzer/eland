<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ElasController extends AbstractController
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

