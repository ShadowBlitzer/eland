<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ContactDetailController extends AbstractController
{
	/**
	 * @Route("/contact-details", name="contact_detail_index")
	 * @Method({"GET", "POST"})
	 */
	public function index(Request $request, string $schema)
	{
		return $this->render('contact_detail/a_index.html.twig', []);
	}
}

