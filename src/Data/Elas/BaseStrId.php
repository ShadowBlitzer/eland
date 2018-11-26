<?php declare(strict_types=1);

namespace App\Data\Elas;

use App\Data\Elas\Base;

abstract class BaseStrId extends Base
{

    public function __construct(string $system, string $id, array $data)
    {
        $this->system = $system;
        $this->id = $id;
        $this->data = $data;
    }

    public function getId():string
    {
        return $id;
    }
}
