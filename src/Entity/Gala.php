<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Gala
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateHeure;

    #[ORM\ManyToOne(targetEntity: Salle::class, inversedBy: 'galas')]
    #[ORM\JoinColumn(nullable: false)]
    private Salle $salle;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $billetwebEventId = null;

    #[ORM\Column]
    private int $placesDisponibles;

    // Getters/Setters
    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getDateHeure(): \DateTimeInterface { return $this->dateHeure; }
    public function setDateHeure(\DateTimeInterface $dateHeure): self { $this->dateHeure = $dateHeure; return $this; }
    public function getSalle(): Salle { return $this->salle; }
    public function setSalle(Salle $salle): self { $this->salle = $salle; return $this; }
    public function getBilletwebEventId(): ?string { return $this->billetwebEventId; }
    public function setBilletwebEventId(?string $billetwebEventId): self { $this->billetwebEventId = $billetwebEventId; return $this; }
    public function getPlacesDisponibles(): int { return $this->placesDisponibles; }
    public function setPlacesDisponibles(int $placesDisponibles): self { $this->placesDisponibles = $placesDisponibles; return $this; }
}
