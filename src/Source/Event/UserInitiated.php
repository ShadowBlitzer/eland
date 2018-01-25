<?php 

namespace App\Source\Event;

class UserInitiated
{
    public function getIdMustBeUnique()
    {
        return true;
    }
}