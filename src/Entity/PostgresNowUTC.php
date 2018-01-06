<?php

namespace App\Entity\DateTimeNowUTC;

class PostgresNowUTC
{
    public function format() 
    {
        return 'timezone(\'utc\', now())';
    }
}