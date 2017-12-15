<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ContactDetailController extends AbstractController
{
	public function index(Request $request, string $schema)
	{
		return $this->render('contact_detail/a_index.html.twig', []);
	}
}

