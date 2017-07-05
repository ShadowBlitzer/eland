<?php

namespace controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class transaction
{
	public function index(Request $request, Application $app, string $schema, string $access)
	{
		$data = [
			'andor'	=> 'and',
		];

		$filter = $app['form.factory']->createNamedBuilder('', FormType::class, $data, [
				'csrf_protection'	=> false,
			])
			->setMethod('GET')
			->add('q', TextType::class, ['required' => false])
			->add('fcode', TextType::class, ['required' => false])
			->add('tcode', TextType::class, ['required' => false])
			->add('andor', ChoiceType::class, [
				'required' 	=> true,
				'choices'	=> [
					'and'	=> 'and',
					'or'	=> 'or',
					'nor'	=> 'nor',
				],
			])
			->add('fdate', TextType::class, ['required' => false])
			->add('tdate', TextType::class, ['required' => false])
			->add('z', SubmitType::class)
			->getForm();

		$filter->handleRequest($request);


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


		$s_owner = (!$s_guest && $s_group_self && $s_id == $uid && $uid) ? true : false;

		$params_sql = $where_sql = $where_code_sql = [];

		$params = [
			'orderby'	=> $orderby,
			'asc'		=> $asc,
			'limit'		=> $limit,
			'start'		=> $start,
		];

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

		if ($q)
		{
			$where_sql[] = 't.description ilike ?';
			$params_sql[] = '%' . $q . '%';
			$params['q'] = $q;
		}

		if (!$uid)
		{
			if ($fcode)
			{
				list($fcode) = explode(' ', trim($fcode));

				$fuid = $app['db']->fetchColumn('select id from users where letscode = \'' . $fcode . '\'');

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

				$tuid = $app['db']->fetchColumn('select id from users where letscode = \'' . $tcode . '\'');

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
		}

		$where_sql = array_merge($where_sql, $where_code_sql);

		if ($fdate)
		{
			$fdate_sql = $app['eland.date_format']->reverse($fdate);

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
			$tdate_sql = $app['eland.date_format']->reverse($tdate);

			if ($tdate_sql === false)
			{
				$app['eland.alert']->warning('De einddatum is fout geformateerd.');
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

		$query = 'select t.*
			from transactions t ' .
			$where_sql . '
			order by t.' . $orderby . ' ';
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
			from transactions t ' . $where_sql, $params_sql);

		return $app['twig']->render('transaction/' . $access . '_index.html.twig', [
			'transactions'	=> $transactions,
//			'pagination'	=> $app['util.pagination']->get('transactions', $row_count, $params),
			'filter'		=> $filter->createView(),
		]);
	}


	public function show(Request $request, Application $app, string $schema, string $access, array $transaction)
	{
		$news = $app['db']->fetchAssoc('SELECT n.*
			FROM news n
			WHERE n.id = ?', [$id]);

		if (!$s_admin && !$news['approved'])
		{
			$app['eland.alert']->error('Je hebt geen toegang tot dit nieuwsbericht.');
			cancel();
		}

		$news_access = $app['eland.xdb']->get('news_access', $id)['data']['access'];

		if (!$app['eland.access_control']->is_visible($news_access))
		{
			$app['eland.alert']->error('Je hebt geen toegang tot dit nieuwsbericht.');
			cancel();
		}

		$and_approved_sql = ($s_admin) ? '' : ' and approved = \'t\' ';

		$rows = $app['eland.xdb']->get_many(['agg_schema' => $app['eland.this_group']->get_schema(),
			'agg_type' => 'news_access',
			'eland_id' => ['<' => $news['id']],
			'access' => $app['eland.access_control']->get_visible_ary()], 'order by eland_id desc limit 1');

		$prev = (count($rows)) ? reset($rows)['eland_id'] : false;

		$rows = $app['eland.xdb']->get_many(['agg_schema' => $app['eland.this_group']->get_schema(),
			'agg_type' => 'news_access',
			'eland_id' => ['>' => $news['id']],
			'access' => $app['eland.access_control']->get_visible_ary()], 'order by eland_id asc limit 1');

		$next = (count($rows)) ? reset($rows)['eland_id'] : false;


		return $app['twig']->render('transaction/' . $access . '_show.html.twig', [
			'news'	=> $news,
		]);
	}


	public function add(Request $request, Application $app, string $schema, string $access)
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

	public function edit(Request $request, Application $app, string $schema, string $access)
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
