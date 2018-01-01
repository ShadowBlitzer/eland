<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class MassTransactionController extends AbstractController
{
	public function form(Request $request, string $schema)
	{
		return $this->render('mass_transaction/a_form.html.twig', []);
	}
}

