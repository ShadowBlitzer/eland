<?php

namespace App\Filter;

use Symfony\Component\HttpFoundation\Request;
use App\Form\Filter\UserFilterType;

use App\Filter\AbstractFormFilter;

class UserFilter extends AbstractFormFilter
{
    public function filter()
    {
        $this->where = $this->params = [];

		$this->filter = $this->formFactory->createNamedBuilder('f', UserFilterType::class)
			->getForm()
			->handleRequest($this->request);

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
    }
}
