<?php

namespace controller;

use util\app;
use form\category_type;
use exception\not_empty_exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class category
{
	public function index(Request $request, app $app, string $schema)
	{
		$categories = $app['db']->fetchAll('select * 
			from ' . $schema . '.categories 
			order by fullname');
		
		$child_count_ary = [];
		
		foreach ($categories as $cat)
		{
			if (!isset($child_count_ary[$cat['id_parent']]))
			{
				$child_count_ary[$cat['id_parent']] = 0;
			}
		
			$child_count_ary[$cat['id_parent']]++;
		}

		foreach ($categories as &$cat)
		{
			if (isset($child_count_ary[$cat['id']]))
			{
				$cat['child_count'] = $child_count_ary[$cat['id']];
			}
		}

		return $app['twig']->render('category/a_index.html.twig', [
			'categories'	=> $categories,
		]);
	}

	public function add(Request $request, app $app, string $schema, int $parent_category)
	{
		$data = [
			'name'		=> '',
			'id_parent'	=> $parent_category,
		];

		$form = $app['form.factory']->createBuilder(category_type::class, $data)
			->getForm();

		$form->handleRequest($request);

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

			if ($app['db']->insert($schema . '.categories', $data))
			{
				$app->success($app->trans('category_add.success', [
					'%name%'  => $data['name'],
				]));

				return $app->redirect($app->path('category_index', [
					'schema' => $schema,
				]));				
			}

			$app->error($app->trans('category_add.error', [
				'%name%' 	=> $data['name'],
			]));
		}

		return $app['twig']->render('category/a_add.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/*
	*
	*/

	public function edit(Request $request, app $app, string $schema, int $category)
	{
		$count_messages = $app['db']->fetchColumn('select count(*)
			from ' . $schema . '.messages
			where id_category = ?', [$category]);

		$count_subcategories = $app['db']->fetchColumn('select count(*)
			from ' . $schema . '.categories 
			where id_parent = ?', [$category]);

		$data = $app['db']->fetchAssoc('select *
			from ' . $schema . '.categories 
			where id = ?', [$category]);

		$form = $app['form.factory']->createBuilder(category_type::class, $data, [
			'root_selectable'	=> $count_messages ? false : true,
			'sub_selectable'	=> $count_subcategories ? false : true,
		])->getForm();

		$form->handleRequest($request);

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

			if ($app['db']->update($schema . '.categories', $data, ['id' => $category]))
			{
				$app->success($app->trans('category_edit.success', [
					'%name%'  => $data['name'],
				]));

				return $app->redirect($app->path('category_index', [
					'schema' => $schema,
				]));				
			}

			$app->error($app->trans('category_edit.error', [
				'%name%' 	=> $data['name'],
			]));
		}

		return $app['twig']->render('category/a_edit.html.twig', [
			'form'	=> $form->createView(),
			'name'	=> $data['name'],
		]);
	}

	/*
	*
	*/

	public function del(Request $request, app $app, string $schema, int $category)
	{
		if ($app['db']->fetchColumn('select count(*)
			from ' . $schema . '.categories 
			where id_parent = ?', [$category]))
		{
			throw new not_empty_exception(
				'The category has subcategories and thus cannot be deleted.'
			);
		}

		if ($app['db']->fetchColumn('select count(*)
			from ' . $schema . '.messages
			where id_category = ?', [$category]))
		{
			throw new not_empty_exception(
				'The category has messages and thus cannot be deleted.'
			);			
		}

		$data = $app['db']->fetchAssoc('select *
			from ' . $schema . '.categories 
			where id = ?', [$category]);

		$form = $app->form()
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($request);

		if ($form->isValid())
		{
			if ($app['db']->delete($schema . '.categories', ['id' => $category]))
			{
				$app->success($app->trans('category_del.success', [
					'%name%'  => $data['name'],
				]));

				return $app->redirect($app->path('category_index', [
					'schema' => $schema,
				]));				
			}

			$app->error($app->trans('category_del.error', [
				'%name%' 	=> $data['name'],
			]));
		}

		return $app['twig']->render('category/a_del.html.twig', [
			'form'		=> $form->createView(),
			'name'		=> $data['name'],
			'fullname'	=> $data['fullname'],
		]);
	}
}
