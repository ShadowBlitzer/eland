<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\PostgresNowUTC;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 * @ORM\Table(name="event", schema="e")
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ts;

    /**
     * @ORM\Column(type="json_array", options={"jsonb"=true})
     */
    private $data;

    /**
     * @ORM\Column(type="json_array", options={"jsonb"=true})
     */
    private $meta;
}

/*
create table if not exists xdb.ev (
ts timestamp without time zone default timezone('utc'::text, now()),
id uuid not null,
version int not null,
data jsonb,
meta jsonb
);

alter table xdb.ev add primary key (id, version);
*/