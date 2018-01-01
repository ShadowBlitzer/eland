<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class AdController extends AbstractController
{
	/**
	 * @Route("/ads", name="ad_no_view")
	 * @Method("GET")
	 */
	public function noView(Request $request, string $schema, string $access)
	{
		return $app->reroute('ad_index', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $app['view']->get('ad'),
		]);
	}

	/**
	 * @Route("/ads/{view}", name="ad_index")
	 * @Method("GET")
	 */
	public function index(Request $request, string $schema, string $access, string $view = null)
	{
		$app['view']->set('ad', $view);

		return $this->render('ad/' . $access . '_index.html.twig', []);
	}

	/**
	 * @Route("/ads/self", name="ad_self_no_view")
	 * @Method("GET")
	 */
	public function showSelfNoView(Request $request, string $schema, string $access)
	{
		return $app->reroute('ad_show_self', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $app['view']->get('ad'),
		]);		
	}

	/**
	 * @Route("/ads/self/{view}", name="ad_self")
	 * @Method("GET")
	 */
	public function showSelf(Request $request, string $schema, string $access)
	{
		return $this->render('ad/' . $access . '_show_self.html.twig', []);
	}

	/**
	 * @Route("/ads/{id}", name="ad_show")
	 * @Method("GET")
	 */
	public function show(Request $request, string $schema, string $access, array $ad)
	{
		return $this->render('ad/' . $access . '_show.html.twig', [
			'ad'	=> $ad,
		]);
	}
}

