<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use exception\not_empty_exception;
use exception\protected_exception;

class TypeContactController extends AbstractController
{
	private $protected_types = [
		'mail', 'web', 'adr', 'gsm', 'tel',
	];

	/**
	 * @Route("/contact-types", name="type_contact_index")
	 * @Method("GET")
	 */
	public function index(Request $request, string $schema, string $access):Response
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

	/**
	 * @Route("/contact-types/add", name="typecontact_add")
	 * @Method({"GET", "POST"})
	 */
	public function add(Request $request, string $schema, string $access):Response
	{
		$data = [
			'name'		=> '',
			'abbrev'	=> '',
		];

		$form = $this->createForm('type_contact_type', $data)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$app['db']->insert($schema . '.type_contact', $data);

			$this->addFlash('success', 'type_contact_add.success', ['%name%'  => $data['name']]);

			return $this->redirectToRoute('typecontact_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);				
		}

		return $this->render('type_contact/a_add.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/**
	 * @Route("/contact-types/{id}/edit", name="typecontact_edit")
	 * @Method({"GET", "POST"})
	 */
	public function edit(Request $request, string $schema, string $access, array $type_contact):Response
	{
		$id = $type_contact['id'];

		if (in_array($type_contact['abbrev'], $this->protected_types))
		{
			throw new protected_exception(
				'This contact type is protected 
					and cannot be edited.'
			);			
		}

		$form = $this->createForm('type_contact_type', $type_contact, [
			'ignore' => ['id' => $id],
		])->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$app['db']->update($schema . '.type_contact', $data, ['id' => $id]);

			$this->addFlash('success', 'type_contact_edit.success', ['%name%'  => $data['name']]);

			return $this->redirectToRoute('typecontact_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);				
		}

		return $this->render('type_contact/a_edit.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/**
	 * @Route("/contact-types/{id}/del", name="typecontact_del")
	 * @Method({"GET", "POST"})
	 */
	public function del(Request $request, string $schema, string $access, array $type_contact):Response
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

		$form = $this->createFormBuilder()
			->add('submit', SubmitType::class)
			->getForm()
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$app['db']->delete($schema . '.type_contact', ['id' => $id]);

			$this->addFlash('success', 'type_contact_del.success', ['%name%'  => $type_contact['name']]);

			return $this->redirectToRoute('typecontact_index', [
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
