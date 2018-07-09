<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class CustomFieldController extends AbstractController
{
	/**
	 * @Route("/custom-fields", name="custom_field_index", methods={"GET", "POST"})
	 */
	public function index(Request $request, string $schema):Response
	{
		return $this->render('custom_field/a_index.html.twig', []);
	}
}
