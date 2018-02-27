<?php

namespace App\Filter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use App\Form\Filter\TransactionFilterType;

abstract class AbstractFilter
{
    protected $formFactory;
    protected $filter;
    protected $where;
    protected $params;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;        
    }

    abstract public function filter(Request $request);

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