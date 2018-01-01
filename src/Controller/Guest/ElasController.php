<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ElasController extends AbstractController
{
	/**
	 * @Route("/elas-group-login/{id}", name="elas_group_login")
	 * @Method("GET")
	 */
	public function groupLogin(Request $request, string $schema, string $access, string $account)
	{
		return $this->json([]);
	}

	/**
	 * @Route("/elas-soap-status/{id}", name="elas_soap_status")
	 * @Method("GET")
	 */
	public function soapStatus(Request $request, string $schema, string $access, string $account)
	{
		return $this->json([]);
	}
}

