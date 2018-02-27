<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

use App\Util\Sort;
use App\Util\Pagination;
use Doctrine\DBAL\Connection as Db;

use Symfony\Component\Form\FormFactoryInterface;

use App\Repository\TransactionRepository;

use App\Form\Filter\TransactionFilterType;
use App\Form\Post\TransactionType;
use App\Form\Post\TransactionDescriptionType;
use App\Filter\TransactionFilter;

use App\Service\Config;

class TransactionController extends AbstractController
{

	/**
	 * @Route("/transactions", name="transaction_index")
	 * @Method("GET")
	 */
	public function index(TransactionRepository $transactionRepository, 
		TransactionFilter $transactionFilter,
		Request $request, string $schema, string $access)
	{
/*
		$oufti = random_bytes(16);
		var_dump(rtrim(strtr(base64_encode($oufti), '+/', '-_'), '='));
*/
		$transactionFilter->filter($request);

		$rowCount = $transactionRepository->getFilteredRowCount($schema, $transactionFilter);		
		$pagination = new Pagination($request, $rowCount);

		$sort = new Sort($request);

		$sort->addColumns([
			'description'	=> 'asc',
			'amount'		=> 'asc',
			'cdate'			=> 'desc',
		])
			->setDefault('cdate');

		$transactions = $transactionRepository->getFiltered($schema, $transactionFilter, $sort, $pagination);

		return $this->render('transaction/' . $access . '_index.html.twig', [
			'transactions'	=> $transactions,
			'filter'		=> $transactionFilter->createView(),
			'filtered'		=> $transactionFilter->isFiltered(),
			'pagination'	=> $pagination->get(),		
			'sort'			=> $sort->get(),
		]);
	}

	/**
	 * @Route("/transactions/{id}", name="transaction_show", requirements={"id"="\d+"})
	 * @Method("GET")
	 */
	public function show(TransactionRepository $transactionRepository, 
		Request $request, string $schema, string $access, int $id)
	{
		$transaction = $transactionRepository->get($id, $schema);

		return $this->render('transaction/' . $access . '_show.html.twig', [
			'transaction'	=> $transaction,
			'prev'			=> $transactionRepository->getPrev($id, $schema),
			'next'			=> $transactionRepository->getNext($id, $schema),
		]);
	}

	/**
	 * @Route("/transactions/self", name="transaction_show_self")
	 * @Method("GET")
	 */
	public function showSelf(TransactionRepository $transactionRepository, 
		Request $request, string $schema, string $access)
	{
		return $this->render('transaction/' . $access . '_show_self.html.twig', []);
	}

	/**
	 * @Route("/transactions/add", name="transaction_add")
	 * @Method({"GET", "POST"})
	 */
	public function add(TransactionRepository $transactionRepository,
		TranslatorInterface $translator,
		Request $request, string $schema, string $access)
	{
		$form = $this->createForm(TransactionType::class);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			return $this->redirectToRoute('');
		}

		return $this->render('transaction/' . $access . '_add.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/transactions/{id}/edit", name="transaction_edit")
	 * @Method({"GET", "POST"})
	 */
	public function edit(TransactionRepository $transactionRepository, 
		TranslatorInterFace $translator,
		Request $request, string $schema, string $access, int $id)
	{
		$transaction = $transactionRepository->get($id, $schema);

		$form = $this->createForm(TransactionDescriptionType::class, $transaction);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$transactionRepository->updateDescription(
				$transaction['id'], $data['description'], $schema);

			$this->addFlash('success', $translator->trans('transaction_edit.success'));
			
			return $this->redirectToRoute('transaction_show', [
				'schema'		=> $schema,
				'access'		=> $access,
				'id'			=> $id,
			]);
		}

		return $this->render('transaction/a_edit.html.twig', [
			'form' 		=> $form->createView(),
		]);
	}
}

