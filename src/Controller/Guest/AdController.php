<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

use App\Service\SessionView;

class AdController extends AbstractController
{
	/**
	 * @Route("/ads",
	 * name="ad_no_view",
	 * methods="GET")
	 */
	public function noView(SessionView $sessionView, Request $request, string $schema, string $access):Response
	{
		return $this->redirectToRoute('ad_index', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $sessionView->get('ad', $schema, $access),
		]);
	}

	/**
	 * @Route("/ads/{view}",
	 * name="ad_index",
	 * methods="GET")
	 */
	public function index(SessionView $sessionView,
		Request $request, string $schema, string $access, string $view):Response
	{
		$sessionView->set('ad', $schema, $access, $view);

		return $this->render('ad/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/ads/self",
	 * name="ad_self_no_view",
	 * methods="GET")
	 */
	public function showSelfNoView(SessionView $sessionView, Request $request, string $schema, string $access):Response
	{
		return $this->redirectToRoute('ad_show_self', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $sessionView->get('ad', $schema, $access),
		]);
	}

	/**
	 * @Route("/ads/self/{view}",
	 * name="ad_self",
	 * methods="GET")
	 */
	public function showSelf(Request $request, string $schema, string $access):Response
	{
		return $this->render('ad/' . $access . '_show_self.html.twig', []);
	}

	/**
	 * @Route("/ads/{id}",
	 * name="ad_show",
	 * methods="GET")
	 */
	public function show(Request $request, string $schema, string $access, int $id):Response
	{
		return $this->render('ad/' . $access . '_show.html.twig', [
			'ad'	=> $ad,
		]);
	}

	/**
	 * @Route("/ads/add",
	 * name="ad_add",
	 * methods={"GET","POST"})
	 */
	public function add(Request $request, string $schema, string $access):Response
	{
		return $this->render('ad/' . $access . '_add.html.twig', [
			'ad'	=> $ad,
		]);
	}

	/**
	 * @Route("/ads/edit/{id}",
	 * name="ad_edit",
	 * methods={"GET","POST"})
	 */
	public function edit(Request $request, string $schema, string $access, int $id):Response
	{
		return $this->render('ad/' . $access . '_edit.html.twig', [
			'ad'	=> $ad,
		]);
	}

	/**
	 * @Route("/ads/del/{id}",
	 * name="ad_del",
	 * methods={"GET","POST"})
	 */
	public function del(Request $request, string $schema, string $access, int $id):Response
	{
		return $this->render('ad/' . $access . '_edit.html.twig', [
			'ad'	=> $ad,
		]);
	}
}
