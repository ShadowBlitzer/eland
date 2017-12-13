<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CustomFieldController extends Controller
{
	public function indexAction(Request $request, string $schema)
	{
		return $this->render('custom_field/a_index.html.twig', []);
	}
}

