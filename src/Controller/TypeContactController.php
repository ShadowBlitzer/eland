<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use exception\not_empty_exception;
use exception\protected_exception;

class TypeContactController extends AbstractController
{
	private $protected_types = [
		'mail', 'web', 'adr', 'gsm', 'tel',
	];

	public function index(Request $request, string $schema, string $access)
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

		return $this->render('type_contact/a_index.html.twig', [
			'types'					=> $types,
			'contact_count_ary' 	=> $contact_count_ary,
			'protected_types'		=> $this->protected_types,
		]);
	}

	/*
	*
	*/

	public function add(Request $request, string $schema, string $access)
	{
		$data = [
			'name'		=> '',
			'abbrev'	=> '',
		];

		$form = $app->build_form('type_contact_type', $data)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$app['db']->insert($schema . '.type_contact', $data);

			$this->addFlash('success', 'type_contact_add.success', ['%name%'  => $data['name']]);

			return $app->reroute('typecontact_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);				
		}

		return $this->render('type_contact/a_add.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/*
	*
	*/

	public function edit(Request $request, string $schema, string $access, array $type_contact)
	{
		$id = $type_contact['id'];

		if (in_array($type_contact['abbrev'], $this->protected_types))
		{
			throw new protected_exception(
				'This contact type is protected 
					and cannot be edited.'
			);			
		}

		$form = $app->build_form('type_contact_type', $type_contact, [
			'ignore' => ['id' => $id],
		])->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$app['db']->update($schema . '.type_contact', $data, ['id' => $id]);

			$this->addFlash('success', 'type_contact_edit.success', ['%name%'  => $data['name']]);

			return $app->reroute('typecontact_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);				
		}

		return $this->render('type_contact/a_edit.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	public function del(Request $request, string $schema, string $access, array $type_contact)
	{
		$id = $type_contact['id'];

		if (in_array($type_contact['abbrev'], $this->protected_types))
		{
			throw new protected_exception(
				'This contact type is protected 
					and cannot be deleted.'
			);			
		}

		if ($app['db']->fetchColumn('select count(*)
			from ' . $schema . '.contact 
			where id_type_contact = ?', [$id]))
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

		if ($form->isSubmitted() && $form->isValid())
		{
			$app['db']->delete($schema . '.type_contact', ['id' => $id]);

			$this->addFlash('success', 'type_contact_del.success', ['%name%'  => $type_contact['name']]);

			return $app->reroute('typecontact_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);				
		}

		return $this->render('type_contact/a_del.html.twig', [
			'form'		=> $form->createView(),
			'name'		=> $type_contact['name'],
			'abbrev'	=> $type_contact['abbrev'],
		]);
	}
}
