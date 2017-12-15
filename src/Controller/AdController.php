<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AdController extends AbstractController
{
	public function noView(Request $request, string $schema, string $access)
	{
		return $app->reroute('ad_index', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $app['view']->get('ad'),
		]);
	}

	public function index(Request $request, string $schema, string $access, string $view = null)
	{
		$app['view']->set('ad', $view);

		return $this->render('ad/' . $access . '_index.html.twig', []);
	}

	public function showSelfNoView(Request $request, string $schema, string $access)
	{
		return $app->reroute('ad_show_self', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $app['view']->get('ad'),
		]);		
	}

	public function showSelf(Request $request, string $schema, string $access)
	{
		return $this->render('ad/' . $access . '_show_self.html.twig', []);
	}

	public function show(Request $request, string $schema, string $access, array $ad)
	{
		return $this->render('ad/' . $access . '_show.html.twig', [
			'ad'	=> $ad,
		]);
	}
}

