<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
/*
                          Table "xdb.cache"
 Column  |            Type             |              Modifiers
---------+-----------------------------+--------------------------------------
 id      | character varying(255)      | not null
 data    | jsonb                       |
 ts      | timestamp without time zone | default timezone('utc'::text, now())
 expires | timestamp without time zone |
Indexes:
    "cache_pkey" PRIMARY KEY, btree (id)
*/
/**
 * @ORM\Entity(repositoryClass="App\Repository\CacheRepository")
 * @ORM\Table(name="cache", schema="c")
 */
class Cache
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\Column(type="json_array", options={"jsonb"=true})
     */
    private $data;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ts;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expires;
}
