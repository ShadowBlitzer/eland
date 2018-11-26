<?php declare(strict_types=1);

namespace App\Data\Elas;

abstract class Base
{
    protected $system;
    protected $id;
    protected $data;

    public function __construct(string $system, $id, array $data)
    {
        $this->system = $system;
        $this->id = $id;
        $this->data = $data;
    }

    public function getSystem():string
    {
        return $this->system;
    }

    public function getData():array
    {
        return $this->data;
    }

    public function getId()
    {
        return $id;
    }
}
