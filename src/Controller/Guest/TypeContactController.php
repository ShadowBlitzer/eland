<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Translation\TranslatorInterface;

use App\Repository\TypeContactRepository;
use App\Form\Post\TypeContactType;

class TypeContactController extends AbstractController
{
	private $protectedTypes = [
		'mail', 'web', 'adr', 'gsm', 'tel',
	];

	/**
	 * @Route("/contact-types",
	 * name="type_contact_index",
	 * methods="GET")
	 */
	public function index(TypeContactRepository $typeContactRepository,
		Request $request, string $schema, string $access):Response
	{
		$contactTypes = $typeContactRepository->getAllWithCount($schema);

		return $this->render('type_contact/a_index.html.twig', [
//			'types'					=> $types,
//			'contact_count_ary' 	=> $contact_count_ary,
			'contact_types'			=> $contactTypes,
			'protected_types'		=> $this->protectedTypes,
		]);
	}

	/**
	 * @Route("/contact-types/add",
	 * name="type_contact_add",
	 * methods={"GET", "POST"})
	 */
	public function add(TypeContactRepository $typeContactRepository,
		TranslatorInterface $translator,
		Request $request, string $schema, string $access):Response
	{
		$data = [
			'name'		=> '',
			'abbrev'	=> '',
		];

		$form = $this->createForm(TypeContactType::class, $data)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$typeContactRepository->create($schema, $data);
//			$app['db']->insert($schema . '.type_contact', $data);

			$this->addFlash('success', $translator->trans('type_contact_add.success', ['%name%'  => $data['name']]));

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
	 * @Route("/contact-types/{id}/edit",
	 * name="type_contact_edit",
	 * methods={"GET", "POST"})
	 */
	public function edit(
		TypeContactRepository $typeContactRepository,
		TranslatorInterface $translator,
		Request $request, string $schema, string $access, int $id):Response
	{
		$typeContact = $typeContactRepository->get($id, $schema);

		if (in_array($typeContact['abbrev'], $this->protectedTypes))
		{
			throw new ConflictHttpException(
				'This contact type is protected
					and cannot be edited.'
			);
		}

		$form = $this->createForm(TypeContactType::class, $typeContact, [
			'ignore' => ['id' => $id],
		])->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$typeContactTypeRepository->update($id, $schema, $data);

//			$app['db']->update($schema . '.type_contact', $data, ['id' => $id]);

			$this->addFlash('success', $translator->trans('type_contact_edit.success', ['%name%'  => $data['name']]));

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
	 * @Route("/contact-types/{id}/del",
	 * name="type_contact_del",
	 * methods={"GET", "POST"})
	 */
	public function del(TypeContactRepository $typeContactRepository,
		TranslatorInterface $translator,
		Request $request, string $schema, string $access, int $id):Response
	{
		$typeContact = $typeContactRepository->get($id, $schema);

		if (in_array($typeContact['abbrev'], $this->protectedTypes))
		{
			throw new ConflictHttpException(
				'This contact type is protected
					and cannot be deleted.'
			);
		}

		if ($typeContactRepository->getContactCount($id, $schema) !== 0)
		{
			throw new ConflictHttpException(
				'The contact type cannot be deleted
					because contacts of this type exist.'
			);
		}
/*
		if ($app['db']->fetchColumn('select count(*)
			from ' . $schema . '.contact
			where id_type_contact = ?', [$id]))
		{

		}
*/
		$form = $this->createFormBuilder()
			->add('submit', SubmitType::class)
			->getForm()
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
//			$app['db']->delete($schema . '.type_contact', ['id' => $id]);

			$typeContactRepository->delete($id, $schema);

			$this->addFlash('success', $translator->trans('type_contact_del.success', ['%name%'  => $type_contact['name']]));

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
