<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class DocController extends AbstractController
{
	public function index(Request $request, string $schema, string $access)
	{


		return $this->render('doc/' . $access . '_index.html.twig', []);
	}

	public function map(Request $request, string $schema, string $access, array $doc)
	{


		return $this->render('doc/' . $access . '_index.html.twig', []);
	}

	public function show(Request $request, string $schema, string $access, array $forum)
	{
		return $this->render('doc/' . $access . '_show.html.twig', [
			'message'	=> $message,
		]);
	}
}

