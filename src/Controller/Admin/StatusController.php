<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class StatusController extends AbstractController
{
	/**
	 * @Route("/status", name="status")
	 * @Method("GET")
	 */
	public function index(Request $request, string $schema, string $access):Response
	{
		return $this->render('status/a_index.html.twig', []);
	}
}

