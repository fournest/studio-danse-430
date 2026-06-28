<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
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

    // Ajout de orphanRemoval pour nettoyer la BDD si on supprime un danseur du foyer
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Danseur::class, orphanRemoval: true)]
    private Collection $danseurs;

    // Champs de sécurité requis pour notre futur UserChecker
    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private bool $isActif = true;

    public function __construct()
    {
        $this->danseurs = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    
    public function getUserIdentifier(): string { return (string) $this->email; }
    
    public function getRoles(): array 
    { 
        $roles = $this->roles; 
        $roles[] = 'ROLE_USER'; 
        return array_unique($roles); 
    }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }
    
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    
    public function eraseCredentials(): void {}
    
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(string $telephone): self { $this->telephone = $telephone; return $this; }
    
    /**
     * @return Collection<int, Danseur>
     */
    public function getDanseurs(): Collection { return $this->danseurs; }
    
    public function addDanseur(Danseur $danseur): self
    {
        if (!$this->danseurs->contains($danseur)) {
            $this->danseurs->add($danseur);
            $danseur->setParent($this);
        }
        return $this;
    }

    public function removeDanseur(Danseur $danseur): self
    {
        if ($this->danseurs->removeElement($danseur)) {
            // Grâce à orphanRemoval: true, Doctrine supprimera le Danseur de la BDD
            if ($danseur->getParent() === $this) {
                $danseur->setParent(null);
            }
        }
        return $this;
    }

    // Getters et Setters pour la sécurité
    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): self { $this->isVerified = $isVerified; return $this; }

    public function isActif(): bool { return $this->isActif; }
    public function setIsActif(bool $isActif): self { $this->isActif = $isActif; return $this; }

    public function __toString(): string
    {
        return $this->email ?? 'Utilisateur sans email';
    }
}