<?php

namespace App\Filter;

use App\Filter\AbstractFilter;

class UserTypeFilter extends AbstractFilter
{
    private $type;
    private $newUserTreshold;

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function setNewUserTreshold(string $newUserTreshold)
    {
        $this->newUserTreshold = $newUserTreshold;
        return $this;
    }

    public function filter()
    {
        $this->where = $this->params = [];

		switch ($this->type)
		{
			case 'active': 
				$this->where[] = 'u.status in (1, 2, 7)';
				break;
			case 'new':
				$this->where[] = 'u.status = 1';
				$this->where[] = 'u.adate is not null';
				$this->where[] = 'u.adate > ?';
				$params[] = $this->newUserTreshold;
				break;
			case 'leaving':
				$this->where[] = 'u.status = 2';
				break;			
			case 'interlets': 
				$this->where[] = 'u.accountrole = \'interlets\'';
				$this->where[] = 'u.status in (1, 2, 7)';
				break;
			case 'direct':
				$this->where[] = 'u.status in (1, 2, 7)';
				$this->where[] = 'u.accountrole != \'interlets\'';
				break;
			case 'pre-active':
				$this->where[] = 'u.adate is null';
				$this->where[] = 'u.status not in (1, 2, 7)';
				break;
			case 'post-active':
				$this->where[] = 'u.adate is not null';
				$this->where[] = 'u.status not in (1, 2, 7)';
				break;
			case 'all':
				break;
			default: 
				$this->where[] = '1 = 2';
				break;
        }

		$this->where = count($this->where) ? ' where ' . implode(' and ', $this->where) . ' ' : '';    
	}

    public function isFiltered():bool
    {
        return strlen($this->where) > 0;
    }

    public function getWhere():string
    {
        return $this->where;
    }

    public function getParams():array
    {
        return $this->params;
    }

    public function createView():FormView
    {
        return $this->filter->createView();
    }
}
