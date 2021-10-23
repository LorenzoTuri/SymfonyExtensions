<?php

namespace Lturi\SymfonyExtensions\Framework\EntityUtility\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;

trait TimestampedEntity {
    /**
     * @var DateTime
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var DateTime
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    protected $updated;

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    /**
     * @ORM\PrePersist
     * @throws Exception
     */
    public function onPrePersist(): void
    {
        $this->created = new \Safe\DateTime('NOW');
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->updated = new \Safe\DateTime('NOW');
    }
}