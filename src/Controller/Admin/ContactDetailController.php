<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class ContactDetailController extends AbstractController
{
	/**
	 * @Route("/contact-details", name="contact_detail_index", methods={"GET", "POST"})
	 */
	public function index(Request $request, string $schema):Response
	{
		return $this->render('contact_detail/a_index.html.twig', []);
	}
}
