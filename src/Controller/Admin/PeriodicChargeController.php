<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class PeriodicChargeController extends AbstractController
{

	/**
	 * @Route("/periodic-charge", name="periodic_charge")
	 * @Method({"GET", "POST"})
	 */
	public function form(Request $request, string $schema, string $access)
	{
		return $this->render('periodic_charge/a_form.html.twig', []);
	}
}

