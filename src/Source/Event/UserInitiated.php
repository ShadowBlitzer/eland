<?php declare(strict_types=1); 

namespace App\Source\Event;

class UserInitiated
{
    public function getIdMustBeUnique()
    {
        return true;
    }
}