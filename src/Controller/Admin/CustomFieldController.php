<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class CustomFieldController extends AbstractController
{
	/**
	 * @Route("/custom-fields", name="custom_field_index")
	 * @Method({"GET", "POST"})
	 */
	public function index(Request $request, string $schema)
	{
		return $this->render('custom_field/a_index.html.twig', []);
	}
}

