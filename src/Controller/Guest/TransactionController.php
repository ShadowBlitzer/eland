<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use App\Util\Sort;
use App\Util\Pagination;
use Doctrine\DBAL\Connection as Db;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormFactoryInterface;

use App\Form\Input\NumberAddonType;
use App\Form\Input\TextAddonType;
use App\Form\Filter\TransactionFilterType;

use App\Service\Config;

class TransactionController extends AbstractController
{

	/**
	 * @Route("/transactions", name="transaction_index")
	 * @Method("GET")
	 */
	public function index(FormFactoryInterface $formFactory, Db $db, Request $request, string $schema, string $access)
	{
/*
		$oufti = random_bytes(16);
		var_dump(rtrim(strtr(base64_encode($oufti), '+/', '-_'), '='));
*/
		$where = $params = [];

		$filter = $formFactory->createNamedBuilder('f', TransactionFilterType::class, ['andor' => 'and'])
			->getForm()
			->handleRequest($request);

		if ($filter->isSubmitted() && $filter->isValid())
		{
			$data = $filter->getData();

			if (isset($data['q']))
			{
				$where[] = 't.description ilike ?';
				$params[] = '%' . $data['q'] . '%';
			}

			$whereCode = [];

			if (isset($data['from_user']))
			{
				$whereCode[] = $data['andor'] === 'nor' ? 't.id_from <> ?' : 't.id_from = ?';
				$params[] = $data['from_user'];
			}

			if (isset($data['to_user']))
			{
				$whereCode[] = $data['andor'] === 'nor' ? 't.id_to <> ?' : 't.id_to = ?';
				$params[] = $data['to_user'];
			}

			if (count($whereCode) > 1 && $data['andor'] === 'or')
			{
				$whereCode = ['(' . implode(' or ', $whereCode) . ')'];
			}

			$where = array_merge($where, $whereCode);

			if (isset($data['from_date']))
			{
				$where[] = 't.date >= ?';
				$params[] = $data['from_date'];
			}

			if (isset($data['to_date']))
			{
				$where[] = 't.date <= ?';
				$params[] = $data['to_date'];
			}
		}

		$filtered = count($where) ? true : false;
		$where = $filtered ? ' where ' . implode(' and ', $where) . ' ' : '';

		$query = ' from ' . $schema . '.transactions t' . $where;
		$rowCount = $db->fetchColumn('select count(t.*)' . $query, $params);
		$query = 'select t.*' . $query;

		$sort = new Sort($request);

		$sort->addColumns([
			'description'	=> 'asc',
			'amount'		=> 'asc',
			'cdate'			=> 'desc',
		])
			->setDefault('cdate');

		$pagination = new Pagination($request, $rowCount);

		$query .= $sort->query();
		$query .= $pagination->query();

		$transactions = [];

		$rs = $db->executeQuery($query, $params);

		while ($row = $rs->fetch())
		{
			if ($row['real_to'] || $row['real_from'])
			{
				$row['class'] = 'warning';			
			}

			$transactions[] = $row;
		}

	//
		foreach ($transactions as $key => $t)
		{
			if (!($t['real_from'] || $t['real_to']))
			{
				continue;
			}

			$inter_schema = false;

			if (isset($interlets_accounts_schemas[$t['id_from']]))
			{
				$inter_schema = $interlets_accounts_schemas[$t['id_from']];
			}
			else if (isset($interlets_accounts_schemas[$t['id_to']]))
			{
				$inter_schema = $interlets_accounts_schemas[$t['id_to']];
			}

			if ($inter_schema)
			{
				$inter_transaction = $db->fetchAssoc('select t.*
					from ' . $inter_schema . '.transactions t
					where t.transid = ?', [$t['transid']]);

				if ($inter_transaction)
				{
					$transactions[$key]['inter_schema'] = $inter_schema;
					$transactions[$key]['inter_transaction'] = $inter_transaction;
				}
			}
		}

		return $this->render('transaction/' . $access . '_index.html.twig', [
			'transactions'	=> $transactions,
			'filter'		=> $filter->createView(),
			'filtered'		=> $filtered,
			'pagination'	=> $pagination->get(),		
			'sort'			=> $sort->get(),
		]);
	}

	/**
	 * @Route("/transactions/{transaction}", name="transaction_show")
	 * @Method("GET")
	 */
	public function show(Request $request, string $schema, string $access, array $transaction)
	{
		return $this->render('transaction/' . $access . '_show.html.twig', [
			'transaction'	=> $transaction,
			'prev'			=> $app['transaction_repository']->getPrev($transaction['id'], $schema),
			'next'			=> $app['transaction_repository']->getNext($transaction['id'], $schema),
		]);
	}

	/**
	 * @Route("/transactions/self", name="transaction_show_self")
	 * @Method("GET")
	 */
	public function showSelf(Request $request, string $schema, string $access)
	{
		return $this->render('transaction/' . $access . '_show_self.html.twig', []);
	}

	/**
	 * @Route("/transactions/add", name="transaction_add")
	 * @Method({"GET", "POST"})
	 */
	public function add(FormFactoryInterface $formFactory, Request $request, string $schema, string $access)
	{
		$form = $formFactory->createBuilder()
			->add('id_from', TypeaheadUserType::class, [
				'source_id'	=> 'form_id_to',		
			])
			->add('id_to', TypeaheadUserType::class, [
                'source_route'  => 'user_typeahead',
                'source_params' => [
                    'user_type'     => 'all',
                ],
			])
			->add('amount', NumberAddonType::class, [
				'constraints'	=> [
				],
			])
			->add('description', TextAddonType::class, [
				'constraints' 	=> [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 60, 'min' => 1]),
				],
				'attr'	=> [
					'maxlength'	=> 60,
				],
			])
			->add('submit', SubmitType::class)
			->getForm();

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
	public function edit(FormFactoryInterface $formFactory, Request $request, string $schema, string $access, array $trans)
	{
		$form = $this->createFormBuilder($transaction)
			->add('description', TextAddonType::class, [
				'constraints' 	=> [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 60, 'min' => 1]),
				],
				'attr'	=> [
					'maxlength'	=> 60,
				],
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$app['transaction_repository']->updateDescription(
				$transaction['id'], $data['description'], $schema);

			$this->addFlash('success', 'transaction_edit.success');
			
			return $this->redirectToRoute('transaction_show', [
				'schema'		=> $schema,
				'access'		=> $access,
				'transaction'	=> $transaction['id'],
			]);
		}

		return $this->render('transaction/a_edit.html.twig', [
			'form' 		=> $form->createView(),
		]);
	}
}

