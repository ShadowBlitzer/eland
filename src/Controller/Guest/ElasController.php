<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

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

