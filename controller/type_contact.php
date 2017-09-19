<?php

namespace controller;

use util\app;
use form\type_contact_type;
use exception\not_empty_exception;
use exception\protected_exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class type_contact
{
	private $protected_types = [
		'mail', 'web', 'adr', 'gsm', 'tel',
	];

	public function index(Request $request, app $app, string $schema)
	{
		$types = $app['db']->fetchAll('select * 
			from ' . $schema . '.type_contact tc');
		
		$contact_count_ary = [];
		
		$rs = $app['db']->prepare('select id_type_contact, count(id)
			from ' . $schema . '.contact
			group by id_type_contact');

		$rs->execute();
		
		while($row = $rs->fetch())
		{
			$contact_count_ary[$row['id_type_contact']] = $row['count'];
		}

		return $app['twig']->render('type_contact/a_index.html.twig', [
			'types'				=> $types,
			'contact_count_ary' 	=> $contact_count_ary,
			'protected_types'	=> $this->protected_types,
		]);
	}

	/*
	*
	*/

	public function add(Request $request, app $app, string $schema)
	{
		$data = [
			'name'		=> '',
			'abbrev'	=> '',
		];

		$form = $app->build_form(
				type_contact_type::class, 
				$data, 
				['schema' => $schema, 'db' => $app['db']])
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			if ($app['db']->insert($schema . '.type_contact', $data))
			{
				$app->success($app->trans('type_contact_add.success', [
					'%name%'  => $data['name'],
				]));

				return $app->redirect($app->path('typecontact_index', [
					'schema' => $schema,
				]));				
			}

			$app->err($app->trans('type_contact_add.error', [
				'%name%' 	=> $data['name'],
			]));
		}

		return $app['twig']->render('type_contact/a_add.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/*
	*
	*/

	public function edit(Request $request, app $app, string $schema, int $type_contact)
	{
		$data = $app['db']->fetchAssoc('select *
			from ' . $schema . '.type_contact 
			where id = ?', [$type_contact]);

		$form = $app->build_form(
				type_contact_type::class, 
				$data,
				[
					'schema' => $schema, 
					'db' => $app['db'],
					'ignore' => ['id' => $type_contact],
				])
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			if ($app['db']->update($schema . '.type_contact', $data, ['id' => $type_contact]))
			{
				$app->success($app->trans('type_contact_edit.success', [
					'%name%'  => $data['name'],
				]));

				return $app->redirect($app->path('typecontact_index', [
					'schema' => $schema,
				]));				
			}

			$app->err($app->trans('type_contact_edit.error', [
				'%name%' 	=> $data['name'],
			]));
		}

		return $app['twig']->render('type_contact/a_edit.html.twig', [
			'form'	=> $form->createView(),
			'name'	=> $data['name'],
		]);
	}

	public function del(Request $request, app $app, string $schema, int $type_contact)
	{
		$data = $app['db']->fetchAssoc('select *
			from ' . $schema . '.type_contact 
			where id = ?', [$type_contact]);

		if (in_array($data['abbrev'], $this->protected_types))
		{
			throw new protected_exception(
				'This contact type is protected 
					and cannot be deleted.'
			);			
		}

		if ($app['db']->fetchColumn('select count(*)
			from ' . $schema . '.contact 
			where id_type_contact = ?', [$type_contact]))
		{
			throw new not_empty_exception(
				'The contact type cannot be deleted 
					because contacts of this type exist.'
			);
		}

		$form = $app->form()
			->add('submit', SubmitType::class)
			->getForm()
			->handleRequest($request);

		if ($form->isValid())
		{
			if ($app['db']->delete($schema . '.type_contact', ['id' => $type_contact]))
			{
				$app->success($app->trans('type_contact_del.success', [
					'%name%'  => $data['name'],
				]));

				return $app->redirect($app->path('typecontact_index', [
					'schema' => $schema,
				]));				
			}

			$app->err($app->trans('type_contact_del.error', [
				'%name%' 	=> $data['name'],
			]));
		}

		return $app['twig']->render('type_contact/a_del.html.twig', [
			'form'		=> $form->createView(),
			'name'		=> $data['name'],
			'abbrev'	=> $data['abbrev'],
		]);
	}
}
