<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

class DocController extends AbstractController
{
	/**
	 * @Route("/docs", name="doc_index")
	 * @Method("GET")
	 */
	public function index(Request $request, string $schema, string $access):Response
	{


		return $this->render('doc/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/docs/{mapId}", name="doc_map")
	 * @Method("GET")
	 */
	public function map(Request $request, string $schema, string $access, string $mapId):Response
	{


		return $this->render('doc/' . $access . '_index.html.twig', []);
	}
}

