<?php declare(strict_types=1);

namespace App\ColumnSelect;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;

abstract class AbstractColumnSelect
{
    protected $formFactory;
    protected $filter;
    protected $where;
    protected $params;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;        
    }

    abstract public function select(Request $request);

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