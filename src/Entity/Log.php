<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\PostgresNowUTC;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogRepository")
 * @ORM\Table(name="log", schema="c", indexes={@ORM\Index(name="system_idx", columns={"system_id"})})
 * @ORM\HasLifecycleCallbacks
 */
class Log
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ts;

    /**
     * @ORM\Column(name="system_id", type="guid", nullable=true)
     */
    private $system;

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
