<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class MassTransactionController extends Controller
{
	public function form(Request $request, string $schema)
	{
		return $this->render('mass_transaction/a_form.html.twig', []);
	}
}

