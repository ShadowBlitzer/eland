<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\DateTimeUTC;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AggRepository")
 * @ORM\Table(name="agg",
 * schema="c",
 * indexes={@ORM\Index(name="agg_type_system_idx", columns={"agg_type", "system_id"})})
 */
class Agg
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;

    /**
     * @ORM\Column(name="agg_version", type="integer")
     */
    private $version;

    /**
     * @ORM\Column(name="system_id", type="guid", nullable=true)
     */
    private $system;

    /**
     * @ORM\Column(name="agg_type", type="string", nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     */
    private $data;

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     */
    private $meta;
}
