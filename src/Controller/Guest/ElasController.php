<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ElasController extends AbstractController
{
	/**
	 * @Route("/elas-group-login/{id}", name="elas_group_login", methods="GET")
	 */
	public function groupLogin(Request $request, string $schema, string $access, string $account):Response
	{
		return $this->json([]);
	}

	/**
	 * @Route("/elas-soap-status/{id}", name="elas_soap_status", methods="GET")
	 */
	public function soapStatus(Request $request, string $schema, string $access, string $account):Response
	{
		return $this->json([]);
	}
}
