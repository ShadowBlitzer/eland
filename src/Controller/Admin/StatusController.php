<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatusController extends AbstractController
{
	/**
	 * @Route("/status",
	 * name="status",
	 * methods="GET")
	 */
	public function index(Request $request, string $schema, string $access):Response
	{
		return $this->render('status/a_index.html.twig', []);
	}
}
