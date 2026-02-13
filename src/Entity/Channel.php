<?php

namespace App\Entity;

use App\Enum\ChannelTypeEnum;
use App\Repository\ChannelRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChannelRepository::class)]
#[ORM\UniqueConstraint(
    name: 'unique_channel_name_per_server',
    columns: ['server_id', 'name']
)]
#[UniqueEntity(
    fields: ['server', 'name'],
    message: 'channel.name.unique',
    errorPath: 'name'
)]
class Channel
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\Column(length: 25, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 25)]
    #[Assert\Regex(pattern: '/^[\p{L}0-9-]+$/u')]
    private string $name;

    #[ORM\Column(length: 20, nullable: false, enumType: ChannelTypeEnum::class)]
    #[Assert\NotNull]
    private ChannelTypeEnum $type;

    #[ORM\ManyToOne(inversedBy: 'channels')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Server $server;

    #[ORM\Column(nullable: false)]
    private DateTimeImmutable $createdAt;


    public function __construct(string $name, Server $server)
    {
        $this->id = Uuid::v7();
        $this->type = ChannelTypeEnum::TEXT;
        $this->createdAt = new DateTimeImmutable();
        $this->server = $server;
        $this->name = $name;
    }
    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ChannelTypeEnum
    {
        return $this->type;
    }

    public function setType(ChannelTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
