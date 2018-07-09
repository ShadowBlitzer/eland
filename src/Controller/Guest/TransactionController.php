<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
use App\Filter\FilterQuery;

use App\Service\Config;

class TransactionController extends AbstractController
{

	/**
	 * @Route("/transactions",
	 * name="transaction_index",
	 * methods="GET")
	 */
	public function index(TransactionRepository $transactionRepository,
		TransactionFilter $transactionFilter,
		Request $request, string $schema, string $access):Response
	{
		$transactionFilter->setRequest($request)
			->filter();

		$filterQuery = new FilterQuery();
		$filterQuery->add($transactionFilter);

		$rowCount = $transactionRepository->getFilteredRowCount($schema, $filterQuery);
		$pagination = new Pagination($request, $rowCount);

		$sort = new Sort($request);

		$sort->addColumns([
			'description'	=> 'asc',
			'amount'		=> 'asc',
			'cdate'			=> 'desc',
		])
			->setDefault('cdate');

		$transactions = $transactionRepository->getFiltered($schema, $filterQuery, $sort, $pagination);

		return $this->render('transaction/' . $access . '_index.html.twig', [
			'transactions'	=> $transactions,
			'filter'		=> $transactionFilter->createView(),
			'filtered'		=> $transactionFilter->isFiltered(),
			'pagination'	=> $pagination->get(),
			'sort'			=> $sort->get(),
		]);
	}

	/**
	 * @Route("/transactions/{id<\d+>}",
	 * name="transaction_show",
	 * methods="GET")
	 */
	public function show(TransactionRepository $transactionRepository,
		Request $request, string $schema, string $access, int $id):Response
	{
		$transaction = $transactionRepository->get($id, $schema);

		return $this->render('transaction/' . $access . '_show.html.twig', [
			'transaction'	=> $transaction,
			'prev'			=> $transactionRepository->getPrev($id, $schema),
			'next'			=> $transactionRepository->getNext($id, $schema),
		]);
	}

	/**
	 * @Route("/transactions/self",
	 * name="transaction_show_self",
	 * methods="GET")
	 */
	public function showSelf(TransactionRepository $transactionRepository,
		Request $request, string $schema, string $access):Response
	{
		return $this->render('transaction/' . $access . '_show_self.html.twig', []);
	}

	/**
	 * @Route("/transactions/add",
	 * name="transaction_add",
	 * methods={"GET", "POST"})
	 */
	public function add(TransactionRepository $transactionRepository,
		TranslatorInterface $translator,
		Request $request, string $schema, string $access):Response
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
	 * @Route("/transactions/edit/{id}",
	 * name="transaction_edit",
	 * methods={"GET", "POST"})
	 */
	public function edit(TransactionRepository $transactionRepository,
		TranslatorInterFace $translator,
		Request $request, string $schema, string $access, int $id):Response
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
