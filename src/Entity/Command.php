<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommandRepository")
 * @ORM\Table(name="command", schema="e",
 *     indexes={@ORM\Index(name="command_idx", columns={"agg_id"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="agg_version_unique_idx", columns={"agg_id", "agg_version"})}
 * )
 */
class Command
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

    public function getSequenceId(): ?int
    {
        return $this->sequenceId;
    }

    public function getAggId(): ?string
    {
        return $this->aggId;
    }

    public function setAggId(string $aggId): self
    {
        $this->aggId = $aggId;

        return $this;
    }

    public function getAggVersion(): ?int
    {
        return $this->aggVersion;
    }

    public function setAggVersion(int $aggVersion): self
    {
        $this->aggVersion = $aggVersion;

        return $this;
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
}
