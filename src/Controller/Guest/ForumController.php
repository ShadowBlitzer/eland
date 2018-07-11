<?php declare(strict_types=1);

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class ForumController extends AbstractController
{
	/**
	 * @Route("/forum",
	 * name="forum_index",
	 * methods="GET")
	 */
	public function index(Request $request, string $schema, string $access):Response
	{


		return $this->render('forum/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/forum/map/{mapId}",
	 * name="forum_show",
	 * methods="GET")
	 */
	public function map(Request $request, string $schema, string $access, string $mapId):Response
	{


		return $this->render('forum/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/forum/{id}",
	 * name="forum_show",
	 * methods="GET")
	 */
	public function show(Request $request, string $schema, string $access, string $id):Response
	{
		return $this->render('forum/' . $access . '_show.html.twig', [
			'message'	=> $message,
		]);
	}
}
