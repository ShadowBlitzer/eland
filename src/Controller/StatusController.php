<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class StatusController extends AbstractController
{
	public function index(Request $request, string $schema, string $access)
	{
		return $this->render('status/a_index.html.twig', []);
	}
}

