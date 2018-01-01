<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/*
ad_show_self_no_view:
    controller: App\Controller\AdController::showSelfNoView
    path: /self

ad_show_self:
    controller: App\Controller\AdController::showSelf
    path: /self/{view}
    requirements:
        view: '%app.ad.views%'
  
ad_add:
    controller: App\Controller\AdController::add
    path: /add

ad_edit:
    controller: App\Controller\AdController::edit
    path: /{ad}/edit
    requirements:
        ad: '\d++'

ad_del:
    controller: App\Controller\AdController::del
    path: /{ad}/del
    requirements:
        ad: '\d++'
*/


class AdController extends AbstractController
{
	/*
	* @Route("/transactions", name="transaction_index", requirements={"_locale"="%})
	*/
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

