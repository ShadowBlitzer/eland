<?php

namespace App\Filter;

use Symfony\Component\HttpFoundation\Request;
use App\Form\Filter\TransactionFilterType;

use App\Filter\AbstractFilter;

class TransactionFilter extends AbstractFilter
{
    public function filter(Request $request)
    {
        $this->where = $this->params = [];

		$this->filter = $this->formFactory->createNamedBuilder('f', TransactionFilterType::class, ['andor' => 'and'])
			->getForm()
			->handleRequest($request);

		if ($this->filter->isSubmitted() && $this->filter->isValid())
		{
			$data = $this->filter->getData();

			if (isset($data['q']))
			{
				$this->where[] = 't.description ilike ?';
				$this->params[] = '%' . $data['q'] . '%';
			}

			$whereCode = [];

			if (isset($data['from_user']))
			{
				$whereCode[] = $data['andor'] === 'nor' ? 't.id_from <> ?' : 't.id_from = ?';
				$this->params[] = $data['from_user'];
			}

			if (isset($data['to_user']))
			{
				$whereCode[] = $data['andor'] === 'nor' ? 't.id_to <> ?' : 't.id_to = ?';
				$this->params[] = $data['to_user'];
			}

			if (count($whereCode) > 1 && $data['andor'] === 'or')
			{
				$whereCode = ['(' . implode(' or ', $whereCode) . ')'];
			}

			$this->where = array_merge($this->where, $whereCode);

			if (isset($data['from_date']))
			{
				$this->where[] = 't.date >= ?';
				$this->params[] = $data['from_date'];
			}

			if (isset($data['to_date']))
			{
				$this->where[] = 't.date <= ?';
				$this->params[] = $data['to_date'];
			}
        }

        $this->where = count($this->where) ? ' where ' . implode(' and ', $this->where) . ' ' : '';
    }
}