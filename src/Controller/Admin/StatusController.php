<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class StatusController extends AbstractController
{
	/**
	 * @Route("/status", name="status")
	 * @Method("GET")
	 */
	public function index(Request $request, string $schema, string $access)
	{
		return $this->render('status/a_index.html.twig', []);
	}
}

