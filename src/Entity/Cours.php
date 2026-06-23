<?php
namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $nom;

    #[ORM\Column(length: 10)]
    private string $jour;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $heure;

    #[ORM\Column(length: 50)]
    private string $professeur;

    #[ORM\Column]
    private int $capaciteMax;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whatsappGroupLink = null;

    /**
     * @var Collection<int, Danseur>
     */
    #[ORM\ManyToMany(targetEntity: Danseur::class, mappedBy: 'cours')]
    private Collection $danseurs;

    public function __construct()
    {
        $this->danseurs = new ArrayCollection();
    }

    // Getters/Setters
    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getJour(): string { return $this->jour; }
    public function setJour(string $jour): self { $this->jour = $jour; return $this; }
    public function getHeure(): \DateTimeInterface { return $this->heure; }
    public function setHeure(\DateTimeInterface $heure): self { $this->heure = $heure; return $this; }
    public function getProfesseur(): string { return $this->professeur; }
    public function setProfesseur(string $professeur): self { $this->professeur = $professeur; return $this; }
    public function getCapaciteMax(): int { return $this->capaciteMax; }
    public function setCapaciteMax(int $capaciteMax): self { $this->capaciteMax = $capaciteMax; return $this; }
    public function getWhatsAppGroupLink(): ?string { return $this->whatsappGroupLink; }
    public function setWhatsAppGroupLink(?string $whatsappGroupLink): self { $this->whatsappGroupLink = $whatsappGroupLink; return $this; }
    /**
     * @return Collection<int, Danseur>
     */
    public function getDanseurs(): Collection { return $this->danseurs; }

    public function addDanseur(Danseur $danseur): static
    {
        if (!$this->danseurs->contains($danseur)) {
            $this->danseurs->add($danseur);
            $danseur->addCours($this);
        }

        return $this;
    }

    public function removeDanseur(Danseur $danseur): static
    {
        if ($this->danseurs->removeElement($danseur)) {
            $danseur->removeCours($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Cours';
    }
}
