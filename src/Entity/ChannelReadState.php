<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ChannelReadStateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ChannelReadStateRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_channel_read_state', columns: ['owner_id', 'channel_id'])]
class ChannelReadState
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Channel $channel;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\ManyToOne]
    private ?Message $lastReadMessage = null;

    /**
     * @param Channel $channel
     * @param User $owner
     */
    public function __construct(Channel $channel, User $owner)
    {
        $this->id = Uuid::v7();
        $this->channel = $channel;
        $this->owner = $owner;
    }


    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getChannel(): Channel
    {
        return $this->channel;
    }

    public function setChannel(Channel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getLastReadMessage(): ?Message
    {
        return $this->lastReadMessage;
    }

    public function setLastReadMessage(?Message $lastReadMessage): static
    {
        $this->lastReadMessage = $lastReadMessage;

        return $this;
    }
}
