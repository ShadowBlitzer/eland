<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class StatusController extends Controller
{
	public function indexAction(Request $request, string $schema, string $access)
	{
		return $this->render('status/a_index.html.twig', []);
	}
}

