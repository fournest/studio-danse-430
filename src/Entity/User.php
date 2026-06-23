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
    private string $email;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 20)]
    private string $telephone;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Danseur::class)]
    private Collection $danseurs;

    public function __construct()
    {
        $this->danseurs = new ArrayCollection();
    }

    // Getters/Setters
    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { $roles = $this->roles; $roles[] = 'ROLE_USER'; return array_unique($roles); }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}
    public function getTelephone(): string { return $this->telephone; }
    public function setTelephone(string $telephone): self { $this->telephone = $telephone; return $this; }
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
            // Ne rien faire, car la relation est non-nullable
        }
        return $this;
    }
    /**
     * @return string
     */
    public function __toString(): string
    {
        // On retourne l'email qui est une chaîne de caractères unique
        return $this->email ?? 'Utilisateur sans email';
    }
}
