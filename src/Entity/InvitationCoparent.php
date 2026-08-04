<?php

namespace App\Entity;

use App\Repository\InvitationCoparentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvitationCoparentRepository::class)]
#[ORM\Table(name: 'invitation_coparent')]
#[ORM\UniqueConstraint(name: 'UNIQ_INVITATION_COPARENT_TOKEN', columns: ['token'])]
class InvitationCoparent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Danseur $danseur = null;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $acceptedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getDanseur(): ?Danseur
    {
        return $this->danseur;
    }

    public function setDanseur(?Danseur $danseur): static
    {
        $this->danseur = $danseur;

        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function getAcceptedBy(): ?User
    {
        return $this->acceptedBy;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isUsed(): bool
    {
        return null !== $this->usedAt;
    }

    public function isValid(): bool
    {
        return !$this->isUsed() && !$this->isExpired();
    }

    public function markUsed(User $user): static
    {
        $this->usedAt = new \DateTimeImmutable();
        $this->acceptedBy = $user;

        return $this;
    }
}
