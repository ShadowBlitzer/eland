<?php

namespace controller;

use util\app;
use exception\not_empty_exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class category
{
	public function index(Request $request, app $app, string $schema)
	{
		return $app['twig']->render('category/a_index.html.twig', [
			'categories'	=> $app['category_repository']->get_all($schema),
		]);
	}

	/*
	*
	*/

	public function add(Request $request, app $app, string $schema, int $parent_category)
	{
		$data = [
			'name'		=> '',
			'id_parent'	=> $parent_category,
		];

		$form = $app->build_form('category_type', $data)
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$data['cdate'] = gmdate('Y-m-d H:i:s');
			$data['id_creator'] = 14;//($s_master) ? 0 : $s_id;
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

			$app['db']->insert($schema . '.categories', $data);

			$app->success($app->trans('category_add.success', [
				'%name%'  => $data['name'],
			]));

			return $app->redirect($app->path('category_index', [
				'schema' => $schema,
			]));				
		}

		return $app['twig']->render('category/a_add.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/*
	*
	*/

	public function edit(Request $request, app $app, string $schema, array $category)
	{
		$id = $category['id'];

		$count_messages = $app['db']->fetchColumn('select count(*)
			from ' . $schema . '.messages
			where id_category = ?', [$id]);

		$count_subcategories = $app['db']->fetchColumn('select count(*)
			from ' . $schema . '.categories 
			where id_parent = ?', [$id]);

		$form = $app->build_form('category_type', $category, [
			'root_selectable'	=> $count_messages ? false : true,
			'sub_selectable'	=> $count_subcategories ? false : true,
		])->handleRequest($request);

		if ($form->isValid())
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

			$app['db']->update($schema . '.categories', $data, ['id' => $id]);

			$app->success($app->trans('category_edit.success', [
				'%name%'  => $data['name'],
			]));

			return $app->redirect($app->path('category_index', [
				'schema' => $schema,
			]));				
		}

		return $app['twig']->render('category/a_edit.html.twig', [
			'form'	=> $form->createView(),
			'name'	=> $category['name'],
		]);
	}

	/*
	*
	*/

	public function del(Request $request, app $app, string $schema, array $category)
	{
		$id = $category['id'];

		if ($app['db']->fetchColumn('select count(*)
			from ' . $schema . '.categories 
			where id_parent = ?', [$id]))
		{
			throw new not_empty_exception(
				'The category has subcategories and thus cannot be deleted.'
			);
		}

		if ($app['db']->fetchColumn('select count(*)
			from ' . $schema . '.messages
			where id_category = ?', [$id]))
		{
			throw new not_empty_exception(
				'The category has messages and thus cannot be deleted.'
			);			
		}

		$form = $app->form()
			->add('submit', SubmitType::class)
			->getForm()
			->handleRequest($request);

		if ($form->isValid())
		{
			$app['db']->delete($schema . '.categories', ['id' => $id]);

			$app->success($app->trans('category_del.success', [
				'%name%'  => $category['name'],
			]));

			return $app->redirect($app->path('category_index', [
				'schema' => $schema,
			]));
		}

		return $app['twig']->render('category/a_del.html.twig', [
			'form'		=> $form->createView(),
			'name'		=> $category['name'],
			'fullname'	=> $category['fullname'],
		]);
	}
}
