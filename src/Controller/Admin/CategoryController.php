<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

use App\Repository\CategoryRepository;
use App\Form\Post\CategoryType;

use exception\not_empty_exception;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CategoryController extends AbstractController
{
	/**
	 * @Route("/categories", name="category_index")
	 * @Method("GET")
	 */
	public function index(CategoryRepository $categoryRepository, Request $request, string $schema, string $access):Response
	{
		return $this->render('category/a_index.html.twig', [
			'categories'	=> $categoryRepository->getAll($schema),
		]);
	}

	/**
	 * @Route("/categories/add", name="category_add")
	 * @Method({"GET", "POST"})
	 */
	public function add(CategoryRepository $categoryRepository, 
		TranslatorInterface $translator,	
		Request $request, string $schema, string $access, int $parentCategory = null):Response
	{
		$data = [
			'id_parent'	=> $parentCategory,
		];

		$form = $this->createForm(CategoryType::class, $data)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$data['cdate'] = gmdate('Y-m-d H:i:s');
			$data['id_creator'] = 14;//($s_master) ? 0 : $s_id;
			$data['fullname'] = '';
			$data['leafnote'] = 0;

			if ($data['id_parent'])
			{
				$data['leafnote'] = 1;
				$data['fullname'] = $categoryRepository->getName($data['id_parent'], $schema);	
				$data['fullname'] .= ' - ';	
			}
		
			$data['fullname'] .= $data['name'];

			$categoryRepository->insert($schema, $data);

			$this->addFlash('success', 
				$translator->trans('category_add.success', ['%name%'  => $data['name']]));

			return $this->redirectToRoute('category_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);				
		}

		return $this->render('category/a_add.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/**
	 * @Route("/categories/{id}/edit", name="category_edit")
	 * @Method({"GET", "POST"})
	 */
	public function edit(CategoryRepository $categoryRepository, 
		TranslatorInterface $translator,
		Request $request, string $schema, string $access, int $id):Response
	{
		$category = $categoryRepository->get($id, $schema);

		$countAds = $categoryRepository->getCountAds($id, $schema);
		$countSubcategories = $categoryRepository->getCountSubcategories($id, $schema);

		$form = $this->createForm(CategoryType::class, $category, [
			'root_selectable'	=> $countAds ? false : true,
			'sub_selectable'	=> $countSubcategories ? false : true,
		])->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$data['fullname'] = '';
			$data['leafnote'] = 0;

			if ($data['id_parent'])
			{
				$data['leafnote'] = 1;
				$data['fullname'] = $categoryRepository->getName($data['id_parent'], $schema);	
				$data['fullname'] .= ' - ';	
			}
	
			$data['fullname'] .= $data['name'];

			$categoryRepository->update($id, $schema, $data);

			$this->addFlash('success', $translator->trans('category_edit.success', ['%name%'  => $data['name']]));

			return $this->redirectToRoute('category_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);				
		}

		return $this->render('category/a_edit.html.twig', [
			'form'	=> $form->createView(),
		]);
	}

	/**
	 * @Route("/categories/{id}/del", name="category_del")
	 * @Method({"GET", "POST"})
	 */
	public function del(CategoryRepository $categoryRepository, 
		TranslatorInterface $translator,
		Request $request, string $schema, string $access, int $id):Response
	{
		$category = $categoryRepository->get($id, $schema);

		if ($categoryRepository->getCountSubcategories($id, $schema))
		{
			throw new not_empty_exception(
				'The category has subcategories and thus cannot be deleted.'
			);
		}

		if ($categoryRepository->getCountAds($id, $schema))
		{
			throw new not_empty_exception(
				'The category has messages and thus cannot be deleted.'
			);			
		}

		$form = $this->createFormBuilder()
			->add('submit', SubmitType::class)
			->getForm()
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$categoryRepository->delete($id, $schema);

			$this->addFlash('success', $translator->trans('category_del.success', ['%name%'  => $category['name']]));

			return $this->redirectToRoute('category_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
			]);
		}

		return $this->render('category/a_del.html.twig', [
			'form'		=> $form->createView(),
			'name'		=> $category['name'],
			'fullname'	=> $category['fullname'],
		]);
	}
}
