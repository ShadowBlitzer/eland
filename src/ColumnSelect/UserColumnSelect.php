<?php declare(strict_types=1);

namespace App\ColumnSelect;

use Symfony\Component\HttpFoundation\Request;
use App\Form\ColumnSelect\UserColumnSelectType;
use App\ColumnSelect\AbstractColumnSelect;

class UserColumnSelect extends AbstractColumnSelect
{
	private $columns = [
		'base'	=> [
			'letscode'		=> true,
			'name'			=> true,
			'fullname'		=> false,
			'postcode'		=> true,
			'accountrole'	=> false,
			'saldo'			=> true,
			'saldo_date'	=> false,
			'minlimit'		=> false,
			'maxlimit'		=> false,
			'comments'		=> false,
			'admincomment'	=> false,
			'hobbies'		=> false,
			'cron_saldo'	=> false,
			'cdate'			=> false,
			'mdate'			=> false,
			'adate'			=> false,
			'lastlogin'		=> false,
		],
		'c'	=> [

		], 
		'm'	=> [
			'wants'			=> false,
			'offers'		=> false,
			'total'			=> false,
		],
		'a'	=> [
			'transactions'	=> [
				'in'	=> false,
				'out'	=> false,
				'total'	=> false,
			],
			'amount'	=> [
				'in'	=> false,
				'out'	=> false,
				'total'	=> false,
			],
		],
	];

    public function select(Request $request)
    {
		$select = $formFactory->createNamedBuilder('col', UserColumnSelectType::class, $columns)
			->getForm()
			->handleRequest($request);

		if ($select->isSubmitted() && $select->isValid())
		{
			$newColumns = $select->getData();

			$columns = $newColumns;
		}

	/*
        $this->where = $this->params = [];

		$this->filter = $this->formFactory->createNamedBuilder('f', UserFilterType::class)
			->getForm()
			->handleRequest($request);

		if ($this->filter->isSubmitted() && $this->filter->isValid())
		{
			$data = $filter->getData();

			if (isset($data['q']))
			{
				$where_q[] = 'u.name ilike ?';
				$params[] = '%' . $data['q'] . '%';
		
				$where_q[] = 'u.letscode ilike ?';
				$params[] = '%' . $data['q'] . '%';				
			}
        }

		$this->where = count($this->where) ? ' where ' . implode(' or ', $this->where) . ' ' : '';
	*/		

	}
}
