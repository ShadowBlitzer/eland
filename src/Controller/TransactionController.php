<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
	public function index(FormFactoryInterface $formFactory, Db $db, Request $request, string $schema, string $access)
	{
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


	public function show(Request $request, string $schema, string $access, array $transaction)
	{
		return $this->render('transaction/' . $access . '_show.html.twig', [
			'transaction'	=> $transaction,
			'prev'			=> $app['transaction_repository']->getPrev($transaction['id'], $schema),
			'next'			=> $app['transaction_repository']->getNext($transaction['id'], $schema),
		]);
	}

	public function showSelf(Request $request, string $schema, string $access)
	{
		return $this->render('transaction/' . $access . '_show_self.html.twig', []);
	}

	public function add(Request $request, string $schema, string $access)
	{
		$form = $app->form()
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

			// do something with the data

			// redirect somewhere
			return $app->redirect('...');
		}

		return $this->render('transaction/' . $access . '_add.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function edit(Request $request, string $schema, string $access, array $trans)
	{
		$form = $app->form($transaction)
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
			
			return $app->reroute('transaction_show', [
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

