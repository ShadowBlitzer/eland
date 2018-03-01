<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use App\Service\SessionView;

class AdController extends AbstractController
{
	/**
	 * @Route("/ads", name="ad_no_view")
	 * @Method("GET")
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
	 * @Route("/ads/{view}", name="ad_index")
	 * @Method("GET")
	 */
	public function index(SessionView $sessionView, 
		Request $request, string $schema, string $access, string $view):Response
	{
		$sessionView->set('ad', $schema, $access, $view);

		return $this->render('ad/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/ads/self", name="ad_self_no_view")
	 * @Method("GET")
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
	 * @Route("/ads/self/{view}", name="ad_self")
	 * @Method("GET")
	 */
	public function showSelf(Request $request, string $schema, string $access):Response
	{
		return $this->render('ad/' . $access . '_show_self.html.twig', []);
	}

	/**
	 * @Route("/ads/{id}", name="ad_show")
	 * @Method("GET")
	 */
	public function show(Request $request, string $schema, string $access, array $ad):Response
	{
		return $this->render('ad/' . $access . '_show.html.twig', [
			'ad'	=> $ad,
		]);
	}
}

