<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Security\ClubRole;
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

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nom = null;

    // Champs de sécurité requis pour notre futur UserChecker
    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private bool $isActif = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $isActivated = false;

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
        $this->email = mb_strtolower(trim($email));
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

    /**
     * Rôle officiel unique stocké en base (hors ROLE_USER implicite).
     */
    public function getPrimaryClubRole(): string
    {
        return ClubRole::extractPrimaryRole($this->roles);
    }

    public function setPrimaryClubRole(string $role): self
    {
        if (ClubRole::USER === $role || '' === trim($role)) {
            $this->roles = [];
        } else {
            $this->roles = [$role];
        }

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

    public function isActivated(): bool
    {
        return $this->isActivated;
    }

    public function setIsActivated(bool $isActivated): self
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    /**
     * Compte importé en attente de première connexion (mot de passe non défini).
     */
    public function needsActivation(): bool
    {
        return !$this->isActivated;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = null !== $prenom ? trim($prenom) : null;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = null !== $nom ? trim($nom) : null;

        return $this;
    }

    public function getNomComplet(): string
    {
        $parts = array_filter([$this->prenom, $this->nom]);

        return $parts !== [] ? implode(' ', $parts) : (string) $this->email;
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
