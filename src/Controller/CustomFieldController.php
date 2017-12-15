<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class CustomFieldController extends AbstractController
{
	public function index(Request $request, string $schema)
	{
		return $this->render('custom_field/a_index.html.twig', []);
	}
}

