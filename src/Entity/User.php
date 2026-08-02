<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    // Champs de sécurité requis pour notre futur UserChecker
    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private bool $isActif = true;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Foyer $foyer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getTelephone(): ?string
    {
        return null !== $this->telephone ? self::formatTelephone($this->telephone) : null;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = self::formatTelephone($telephone);

        return $this;
    }

    /**
     * Affiche un numéro FR lisible : 0612345678 → 06 12 34 56 78
     */
    public static function formatTelephone(?string $telephone): string
    {
        $raw = trim((string) $telephone);
        if ($raw === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '33') && \strlen($digits) >= 11) {
            $digits = '0'.substr($digits, 2);
        }

        if ($digits === '') {
            return $raw;
        }

        return trim(chunk_split($digits, 2, ' '));
    }

    // Getters et Setters pour la sécurité
    public function isVerified(): bool
    {
        return $this->isVerified;
    }
    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->isActif;
    }
    public function setIsActif(bool $isActif): self
    {
        $this->isActif = $isActif;
        return $this;
    }

    public function __toString(): string
    {
        return $this->email ?? ($this->id ? 'Utilisateur #' . $this->id : 'Utilisateur sans email');
    }

    public function getFoyer(): ?Foyer
    {
        return $this->foyer;
    }

    public function setFoyer(?Foyer $foyer): static
    {
        if ($foyer !== null && $foyer->getUser() !== $this) {
            $foyer->setUser($this);
        }

        $this->foyer = $foyer;

        return $this;
    }
}
