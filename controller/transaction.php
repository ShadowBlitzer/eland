<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use form\addon_type;
use form\typeahead_user_type;
use Symfony\Component\Validator\Constraints as Assert;

class transaction
{
	public function index(Request $request, app $app, string $schema, string $access)
	{
		$data = [
			'andor'	=> 'and',
		];

		$filter = $app->build_named_form('filter', 'transaction_filter_type', $data)
			->handleRequest($request);

		if ($filter->isValid())
		{
			$data = $filter->getData();

			error_log($data['to_code']);
		}

		$inline = isset($_GET['inline']) ? true : false;

		$q = $_GET['q'] ?? '';
		$fcode = $_GET['fcode'] ?? '';
		$tcode = $_GET['tcode'] ?? '';
		$andor = $_GET['andor'] ?? 'and';
		$fdate = $_GET['fdate'] ?? '';
		$tdate = $_GET['tdate'] ?? '';

		$orderby = $_GET['orderby'] ?? 'cdate';
		$asc = $_GET['asc'] ?? 0;
		$limit = $_GET['limit'] ?? 25;
		$start = $_GET['start'] ?? 0;

		$params_sql = $where_sql = $where_code_sql = [];

		$params = [
			'orderby'	=> $orderby,
			'asc'		=> $asc,
			'limit'		=> $limit,
			'start'		=> $start,
		];

/*
		if ($uid)
		{
			$user = readuser($uid);

			$where_sql[] = 't.id_from = ? or t.id_to = ?';
			$params_sql[] = $uid;
			$params_sql[] = $uid;
			$params['uid'] = $uid;

			$fcode = $tcode = link_user($user, false, false);
			$andor = 'or';
		}
*/

		if ($q)
		{
			$where_sql[] = 't.description ilike ?';
			$params_sql[] = '%' . $q . '%';
			$params['q'] = $q;
		}


//		if (!$uid)
//		{
			if ($fcode)
			{
				list($fcode) = explode(' ', trim($fcode));

				$fuid = $app['db']->fetchColumn('select id 
					from ' . $schema . '.users 
					where letscode = ?', [$fcode]);

				if ($fuid)
				{
					$where_code_sql[] = 't.id_from = ?';
					$params_sql[] = $fuid;

					$fcode = link_user($fuid, false, false);
				}
				else
				{
					$where_code_sql[] = '1 = 2';
				}

				$params['fcode'] = $fcode;
			}

			if ($tcode)
			{
				list($tcode) = explode(' ', trim($tcode));

				$tuid = $app['db']->fetchColumn('select id 
					from ' . $schema . '.users 
					where letscode = ?', [$tcode]);

				if ($tuid)
				{
					$where_code_sql[] = 't.id_to = ?';
					$params_sql[] = $tuid;

					$tcode = link_user($tuid, false, false);
				}
				else
				{
					$where_code_sql[] = '1 = 2';
				}

				$params['tcode'] = $tcode;
			}

			if (count($where_code_sql) > 1)
			{
				if ($andor == 'or')
				{
					$where_code_sql = [' ( ' . implode(' or ', $where_code_sql) . ' ) '];
				}

				$params['andor'] = $andor;
			}
//		}

		$where_sql = array_merge($where_sql, $where_code_sql);

		if ($fdate)
		{
			$fdate_sql = $app['date_format']->reverse($fdate);

			if ($fdate_sql === false)
			{
				$app['eland.alert']->warning('De begindatum is fout geformateerd.');
			}
			else
			{
				$where_sql[] = 't.date >= ?';
				$params_sql[] = $fdate_sql;
				$params['fdate'] = $fdate;
			}
		}

		if ($tdate)
		{
			$tdate_sql = $app['date_format']->reverse($tdate);

			if ($tdate_sql === false)
			{
				$app->warning('De einddatum is fout geformateerd.');
			}
			else
			{
				$where_sql[] = 't.date <= ?';
				$params_sql[] = $tdate_sql;
				$params['tdate'] = $tdate;
			}
		}

		if (count($where_sql))
		{
			$where_sql = ' where ' . implode(' and ', $where_sql) . ' ';
		}
		else
		{
			$where_sql = '';
		}

		$query = 'select *
			from ' . $schema . '.transactions ' .
			$where_sql . '
			order by ' . $orderby . ' ';
		$query .= ($asc) ? 'asc ' : 'desc ';
		$query .= ' limit ' . $limit . ' offset ' . $start;

		$transactions = $app['db']->fetchAll($query, $params_sql);

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

		$row_count = $app['db']->fetchColumn('select count(t.*)
			from ' . $schema . '.transactions t ' . $where_sql, $params_sql);

		return $app['twig']->render('transaction/' . $access . '_index.html.twig', [
			'transactions'	=> $transactions,
//			'pagination'	=> $app['pagination']->get($row_count, $params),
			'filter'		=> $filter->createView(),
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
			->add('first_name')
			->add('last_name')
			->add('email', EmailType::class)
			->add('postcode')
			->add('gsm', TextType::class, ['required'	=> false])
			->add('tel', TextType::class, ['required'	=> false])
			->add('zend', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
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

		if ($form->isValid())
		{
			$data = $form->getData();

			// do something with the data

			// redirect somewhere
			return $app->redirect('...');
		}

		return $app['twig']->render('transaction/' . $access . '_register.html.twig', [
			'form' => $form->createView(),
		]);
	}

}

