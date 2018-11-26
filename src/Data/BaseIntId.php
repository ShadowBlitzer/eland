<?php declare(strict_types=1);

namespace App\Data;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class BaseIntId
{
    protected $system;
    protected $id;
    protected $data;

    public function __construct(string $system, int $id, array $data)
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

    public function getId():int
    {
        return $id;
    }
}
