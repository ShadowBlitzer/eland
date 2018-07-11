<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class PeriodicChargeController extends AbstractController
{

	/**
	 * @Route("/periodic-charge",
	 * name="periodic_charge",
	 * methods={"GET", "POST"})
	 */
	public function form(Request $request, string $schema, string $access):Response
	{
		return $this->render('periodic_charge/a_form.html.twig', []);
	}
}
