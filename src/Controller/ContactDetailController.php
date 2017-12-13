<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ContactDetailController extends Controller
{
	public function index(Request $request, string $schema)
	{
		return $this->render('contact_detail/a_index.html.twig', []);
	}
}

