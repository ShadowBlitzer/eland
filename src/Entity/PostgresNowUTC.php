<?php declare(strict_types=1);

namespace App\Entity\DateTimeNowUTC;

class PostgresNowUTC
{
    public function format() 
    {
        return 'timezone(\'utc\', now())';
    }
}