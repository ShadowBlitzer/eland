<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PeriodicChargeController extends Controller
{
	public function form(Request $request, string $schema, string $access)
	{
		return $this->render('periodic_charge/a_form.html.twig', []);
	}
}

