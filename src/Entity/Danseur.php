<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Danseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $nom;

    #[ORM\Column(length: 50)]
    private string $prenom;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dateNaissance;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'danseurs')]
    #[ORM\JoinColumn(nullable: false)]
    private User $parent;

    #[ORM\ManyToMany(targetEntity: Cours::class, inversedBy: 'danseurs')]
    private Collection $cours;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
    }

    // Getters/Setters
    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }
    public function getDateNaissance(): \DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(\DateTimeInterface $dateNaissance): self { $this->dateNaissance = $dateNaissance; return $this; }
    public function getParent(): User { return $this->parent; }
    public function setParent(User $parent): self { $this->parent = $parent; return $this; }
    public function getCours(): Collection { return $this->cours; }
    public function addCours(Cours $cours): self
    {
        if (!$this->cours->contains($cours)) {
            $this->cours->add($cours);
        }
        return $this;
    }
    public function removeCours(Cours $cours): self
    {
        $this->cours->removeElement($cours);
        return $this;
    }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        $this->cours->removeElement($cour);

        return $this;
    }
}
