<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class MassTransactionController extends AbstractController
{

	/**
	 * @Route("/mass-transaction",
	 * name="mass_transaction",
	 * methods={"GET", "POST"})
	 */
	public function form(Request $request, string $schema):Response
	{
		return $this->render('mass_transaction/a_form.html.twig', []);
	}
}
