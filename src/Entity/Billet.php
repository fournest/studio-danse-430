<?php

namespace App\Entity;

use App\Repository\BilletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BilletRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_BILLET_TOKEN', columns: ['token'])]
#[ORM\HasLifecycleCallbacks]
class Billet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: CommandeBoutique::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CommandeBoutique $commande = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 150)]
    private string $nomParticipant = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroPlace = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $token;

    #[ORM\Column(options: ['default' => false])]
    private bool $estValide = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $scanneA = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->token = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ('' === $this->token) {
            $this->token = Uuid::v4()->toRfc4122();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getCommande(): ?CommandeBoutique
    {
        return $this->commande;
    }

    public function setCommande(?CommandeBoutique $commande): static
    {
        $this->commande = $commande;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNomParticipant(): string
    {
        return $this->nomParticipant;
    }

    public function setNomParticipant(string $nomParticipant): static
    {
        $this->nomParticipant = trim($nomParticipant);

        return $this;
    }

    public function getNumeroPlace(): ?string
    {
        return $this->numeroPlace;
    }

    public function setNumeroPlace(?string $numeroPlace): static
    {
        $this->numeroPlace = $numeroPlace ? trim($numeroPlace) : null;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function isEstValide(): bool
    {
        return $this->estValide;
    }

    public function setEstValide(bool $estValide): static
    {
        $this->estValide = $estValide;

        return $this;
    }

    public function getScanneA(): ?\DateTimeImmutable
    {
        return $this->scanneA;
    }

    public function setScanneA(?\DateTimeImmutable $scanneA): static
    {
        $this->scanneA = $scanneA;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function marquerScanne(?\DateTimeImmutable $date = null): static
    {
        $this->estValide = true;
        $this->scanneA = $date ?? new \DateTimeImmutable();

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            'Billet %s — %s',
            $this->nomParticipant !== '' ? $this->nomParticipant : ('#' . ($this->id ?? '?')),
            $this->event?->getNom() ?? 'Événement'
        );
    }
}
