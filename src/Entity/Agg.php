<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\DateTimeUTC;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AggRepository")
 * @ORM\Table(name="agg", schema="c")
 */
class Agg
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;

    /**
     * @ORM\Column(name="version_id", type="integer")
     */
    private $version;

    /**
     * @ORM\Column(name="system_id", type="guid", nullable=true)
     */
    private $system;

    /**
     * @ORM\Column(name="ts", type="datetime")
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
create table if not exists xdb.ag (
    ts timestamp without time zone default timezone('utc'::text, now()),
    id uuid primary key not null,
    type varchar(32),
    segment uuid,
    version int not null,
    data jsonb,
    meta jsonb
    );
    
    create index on xdb.ag(type, segment);
*/   