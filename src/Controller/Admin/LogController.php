<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class LogController extends AbstractController
{

	/**
	 * @Route("/logs", name="log")
	 * @Method("GET")
	 */
	public function index(Request $request, string $schema)
	{
		return $this->render('log/a_index.html.twig', []);

		$filter = $app['form.factory']->createNamedBuilder('', FormType::class, [], [
				'csrf_protection'	=> false,
			])
			->setMethod('GET')
			->add('q', TextType::class, ['required' => false])
			->add('letscode', TextType::class, ['required' => false])
			->add('type', TextType::class, ['required' => false])
			->add('fdate', TextType::class, ['required' => false])
			->add('tdate', TextType::class, ['required' => false])
			->add('z', SubmitType::class)
			->getForm();

		$filter->handleRequest($request);

		if ($filter->isValid())
		{
			var_dump('Filter is valid.');
			$data = $filter->getData();
/*
			// do something with the data

			// redirect somewhere
			return $app->redirect('...');
*/
		}




		$q = $_GET['q'] ?? '';
		$letscode = $_GET['letscode'] ?? '';
		$type = $_GET['type'] ?? '';
		$fdate = $_GET['fdate'] ?? '';
		$tdate = $_GET['tdate'] ?? '';

		$orderby = $_GET['orderby'] ?? 'ts';
		$asc = $_GET['asc'] ?? 0;

		$limit = $_GET['limit'] ?? 25;
		$start = $_GET['start'] ?? 0;

		$app['log_db']->update();

		$params = [
			'orderby'	=> $orderby,
			'asc'		=> $asc,
			'limit'		=> $limit,
			'start'		=> $start,
		];

		$params_sql = $where_sql = [];

		$params_sql[] = $app['this_group']->get_schema();

		if ($letscode)
		{
			list($l) = explode(' ', $letscode);

			$where_sql[] = 'letscode = ?';
			$params_sql[] = strtolower($l);
			$params['letscode'] = $l;
		}

		if ($type)
		{
			$where_sql[] = 'type ilike ?';
			$params_sql[] = strtolower($type);
			$params['type'] = $type;
		}

		if ($q)
		{
			$where_sql[] = 'event ilike ?';
			$params_sql[] = '%' . $q . '%';
			$params['q'] = $q;
		}

		if ($fdate)
		{
			$where_sql[] = 'ts >= ?';
			$params_sql[] = $fdate;
			$params['fdate'] = $fdate;
		}

		if ($tdate)
		{
			$where_sql[] = 'ts <= ?';
			$params_sql[] = $tdate;
			$params['tdate'] = $tdate;
		}

		if (count($where_sql))
		{
			$where_sql = ' and ' . implode(' and ', $where_sql) . ' ';
		}
		else
		{
			$where_sql = '';
		}

		$query = 'select *
			from xdb.logs
				where schema = ?' . $where_sql . '
			order by ' . $orderby . ' ';

		$row_count = $app['db']->fetchColumn('select count(*)
			from xdb.logs
			where schema = ?' . $where_sql, $params_sql);

		$query .= ($asc) ? 'asc ' : 'desc ';
		$query .= ' limit ' . $limit . ' offset ' . $start;

		$logs = $app['db']->fetchAll($query, $params_sql);

		return $this->render('logs/index.html.twig', [
			'logs'			=> $logs,
			'pagination'	=> $app['pagination']->get('logs', $row_count, $params),
			'filter'		=> $filter->createView(),
		]);
	}

}