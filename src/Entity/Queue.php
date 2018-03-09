<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\PostgresNowUTC;

/**
 * @ORM\Entity(repositoryClass="App\Repository\QueueRepository")
 * @ORM\Table(name="queue", schema="c", indexes={@ORM\Index(name="topic_idx", columns={"topic"})})
 * @ORM\HasLifecycleCallbacks
 */
class Queue
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint")
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
     * @ORM\Column(type="integer", options={"default":0})
     */
    private $priority;

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
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
