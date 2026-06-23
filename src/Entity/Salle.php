<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Salle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 255)]
    private string $adresse;

    #[ORM\Column]
    private int $capacite;

    #[ORM\OneToMany(mappedBy: 'salle', targetEntity: Gala::class)]
    private Collection $galas;

    public function __construct()
    {
        $this->galas = new ArrayCollection();
    }

    // Getters/Setters
    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getAdresse(): string { return $this->adresse; }
    public function setAdresse(string $adresse): self { $this->adresse = $adresse; return $this; }
    public function getCapacite(): int { return $this->capacite; }
    public function setCapacite(int $capacite): self { $this->capacite = $capacite; return $this; }
    public function getGalas(): Collection { return $this->galas; }

    public function addGala(Gala $gala): self
    {
        if (!$this->galas->contains($gala)) {
            $this->galas->add($gala);
            $gala->setSalle($this);
        }
        return $this;
    }

    public function removeGala(Gala $gala): self
    {
        if ($this->galas->removeElement($gala)) {
            // Ne rien faire, car la relation est non-nullable
        }
        return $this;
    }
}