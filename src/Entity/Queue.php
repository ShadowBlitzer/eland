<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\PostgresNowUTC;

/**
 * @ORM\Entity(repositoryClass="App\Repository\QueueRepository")
 * @ORM\Table(name="queue", schema="c")
 * @ORM\HasLifecycleCallbacks
 */
class Queue
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $topic;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ts;

    /**
     * @ORM\Column(type="integer")
     */
    private $priority;

    /**
     * @ORM\Column(type="json_array", options={"jsonb"=true})
     */
    private $data;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->ts = new PostgresNowUTC();
    }
}


/*
create table if not exists xdb.queue (
ts timestamp without time zone default timezone('utc'::text, now()),
id bigserial primary key,
topic varchar(60) not null,
data jsonb,
priority int default 0);

create index on xdb.queue(id, priority);

*/