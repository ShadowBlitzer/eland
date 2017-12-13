<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ForumController extends Controller
{
	public function index(Request $request, string $schema, string $access)
	{


		return $this->render('forum/' . $access . '_index.html.twig', []);
	}

	public function map(Request $request, string $schema, string $access, array $forum)
	{


		return $this->render('forum/' . $access . '_index.html.twig', []);
	}

	public function show(Request $request, string $schema, string $access, array $forum)
	{
		return $this->render('forum/' . $access . '_show.html.twig', [
			'message'	=> $message,
		]);
	}


}

