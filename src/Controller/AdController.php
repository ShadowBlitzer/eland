<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AdController extends Controller
{
	public function noViewAction(Request $request, string $schema, string $access)
	{
		return $app->reroute('ad_index', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $app['view']->get('ad'),
		]);
	}

	public function indexAction(Request $request, string $schema, string $access, string $view = null)
	{
		$app['view']->set('ad', $view);

		return $this->render('ad/' . $access . '_index.html.twig', []);
	}

	public function showSelfAction(Request $request, string $schema, string $access)
	{
		return $this->render('ad/' . $access . '_show_self.html.twig', []);
	}

	public function showAction(Request $request, string $schema, string $access, array $ad)
	{
		return $this->render('ad/' . $access . '_show.html.twig', [
			'ad'	=> $ad,
		]);
	}
}

