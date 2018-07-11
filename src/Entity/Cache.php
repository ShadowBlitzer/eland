<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\PostgresNowUTC;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CacheRepository")
 * @ORM\Table(name="cache", schema="c")
 * @ORM\HasLifecycleCallbacks
 */
class Cache
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     */
    private $data;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ts;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expires;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->ts = new PostgresNowUTC();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getTs(): ?\DateTimeInterface
    {
        return $this->ts;
    }

    public function setTs(\DateTimeInterface $ts): self
    {
        $this->ts = $ts;

        return $this;
    }

    public function getExpires(): ?\DateTimeInterface
    {
        return $this->expires;
    }

    public function setExpires(?\DateTimeInterface $expires): self
    {
        $this->expires = $expires;

        return $this;
    }    
}
