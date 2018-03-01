<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

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
	 * @Route("/docs/{map}", name="doc_map")
	 * @Method("GET")
	 */
	public function map(Request $request, string $schema, string $access, array $doc):Response
	{


		return $this->render('doc/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/docs/{id}", name="doc_show")
	 * @Method("GET")
	 */
	public function show(Request $request, string $schema, string $access, array $doc):Response
	{
		return $this->render('doc/' . $access . '_show.html.twig', [
			'doc'	=> $doc,
		]);
	}
}

