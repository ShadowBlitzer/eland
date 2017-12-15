<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class PageController extends AbstractController
{

	public function terms(Request $request)
	{
		return $this->render('page/terms.html.twig', []);
	}

    public function show(Request $request, string $schema, array $page)
    {
		$response = $app->render('page/show.html.twig', [
			'content'	=> $page,
		]);
	
        $response->setEtag(crc32($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);
        return $response;
	}
	
	public function aIndex(Request $request, string $schema, string $access)
	{




		return $this->render('page/a_index.html.twig', [

		]);
	}

	public function aAdd(Request $request, string $schema, string $access)
	{
		$data = [

		];

		$form = $app->build_form('page_type', $data)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();
	
			$data['fullname'] = '';
			$data['leafnote'] = 0;

			if ($data['id_parent'])
			{
				$data['leafnote'] = 1;
				$data['fullname'] = $app['db']->fetchColumn('select name 
					from ' . $schema . '.categories 
					where id = ?', [(int) $data['id_parent']]);	
				$data['fullname'] .= ' - ';	
			}
			$data['fullname'] .= $data['name'];

			$app['xdb']->set();

			$app->success('page_add.success', ['%name%'  => $data['name']]);

			return $app->redirect($app->path('page_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]));				
		}

		return $this->render('page/a_add.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	public function aEdit(Request $request, string $schema, string $access, $page)
	{
		$data = [
			
		];

		$form = $app->build_form('page_type', $data)
			->handleRequest($request);


		return $this->render('page/a_edit.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	public function aDel(Request $request, string $schema, string $access, array $page)
	{
		return $this->render('page/a_del.html.twig', [
			'page'	=> $page,
		]);
	}

	public function aShow(Request $request, string $schema, string $access, array $page)
	{
		return $app->render('page/a_show.html.twig', [
			'page'	=> $page,
		]);
	}

}

