<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 * @ORM\Table(name="event", schema="e", 
 *     indexes={@ORM\Index(name="agg_idx", columns={"agg_id"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="agg_version_unique_idx", columns={"agg_id", "agg_version"})}
 * )
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="sequence_id", type="bigint")
     */
    private $sequenceId;

    /**
     * @ORM\Column(name="agg_id", type="guid")
     */
    private $aggId;

    /**
     * @ORM\Column(name="agg_version", type="integer")
     */
    private $aggVersion;

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     */
    private $data;
}
