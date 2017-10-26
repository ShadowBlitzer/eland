<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use util\sort;
use util\pagination;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use form\number_addon_type;
use form\addon_type;

class transaction
{
	public function index(Request $request, app $app, string $schema, string $access)
	{
		$where = [];
		$params = [];

		$data = [
			'andor' => 'and',
		];

		$filter = $app->build_named_form('f', 'transaction_filter_type', $data)
			->handleRequest($request);

		if ($filter->isSubmitted() && $filter->isValid())
		{
			$data = $filter->getData();

			if (isset($data['q']))
			{
				$where[] = 't.description ilike ?';
				$params[] = '%' . $data['q'] . '%';
			}

			$where_code = [];

			if (isset($data['from_user']))
			{
				$where_code[] = $data['andor'] === 'nor' ? 't.id_from <> ?' : 't.id_from = ?';
				$params[] = $data['from_user'];
			}

			if (isset($data['to_user']))
			{
				$where_code[] = $data['andor'] === 'nor' ? 't.id_to <> ?' : 't.id_to = ?';
				$params[] = $data['to_user'];
			}

			if (count($where_code) > 1 && $data['andor'] === 'or')
			{
				$where_code = ['(' . implode(' or ', $where_code) . ')'];
			}

			$where = array_merge($where, $where_code);

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
		$row_count = $app['db']->fetchColumn('select count(t.*)' . $query, $params);
		$query = 'select t.*' . $query;

		$sort = new sort($request);

		$sort->add_columns([
			'description'	=> 'asc',
			'amount'		=> 'asc',
			'cdate'			=> 'desc',
		])
			->set_default('cdate');

		$pagination = new pagination($request, $row_count);

		$query .= $sort->query();
		$query .= $pagination->query();

		$transactions = $app['db']->fetchAll($query, $params);

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
				$inter_transaction = $app['db']->fetchAssoc('select t.*
					from ' . $inter_schema . '.transactions t
					where t.transid = ?', [$t['transid']]);

				if ($inter_transaction)
				{
					$transactions[$key]['inter_schema'] = $inter_schema;
					$transactions[$key]['inter_transaction'] = $inter_transaction;
				}
			}
		}

//		$row_count = $app['db']->fetchColumn('select count(t.*)
//			from ' . $schema . '.transactions t ' . $where_sql, $params_sql);

		return $app['twig']->render('transaction/' . $access . '_index.html.twig', [
			'transactions'	=> $transactions,
			'filter'		=> $filter->createView(),
			'filtered'		=> $filtered,
			'pagination'	=> $pagination->get($row_count),		
			'sort'			=> $sort->get(),
		]);
	}


	public function show(Request $request, app $app, string $schema, string $access, array $transaction)
	{
		return $app['twig']->render('transaction/' . $access . '_show.html.twig', [
			'transaction'	=> $transaction,
		]);
	}

	public function show_self(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('transaction/' . $access . '_show_self.html.twig', []);
	}

	public function add(Request $request, app $app, string $schema, string $access)
	{

		$data = [
			'name' => 'Your name',
			'email' => 'Your email',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('id_from', 'typeahead_user_type', [
				'source_id'	=> 'form_id_to',		
			])
			->add('id_to', 'typeahead_user_type', [
                'source_route'  => 'user_typeahead',
                'source_params' => [
                    'user_type'     => 'all',
                ],
			])
			->add('amount', number_addon_type::class, [
				'constraints'	=> [
				],
			])
			->add('description', addon_type::class, [
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

		return $app['twig']->render('transaction/' . $access . '_add.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function edit(Request $request, app $app, string $schema, string $access)
	{

		$data = [
			'name' => 'Your name',
			'email' => 'Your email',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('first_name')
			->add('last_name')
			->add('email', EmailType::class)
			->add('postcode')
			->add('gsm', TextType::class, ['required'	=> false])
			->add('tel', TextType::class, ['required'	=> false])
			->add('zend', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			// do something with the data

			// redirect somewhere
			return $app->redirect('...');
		}

		return $app['twig']->render('transaction/' . $access . '_register.html.twig', [
			'form' 		=> $form->createView(),
			'filtered'	=> $filtered,
		]);
	}

}

