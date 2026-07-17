<?php

namespace App\Entity;

use App\Repository\DanseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
// Assure-toi d'avoir ces imports :
use App\Entity\Cours; 
use App\Entity\Inscription;

#[ORM\Entity(repositoryClass: DanseurRepository::class)]
class Danseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateNaissance = null;

    /**
     * @var Collection<int, Cours>
     */
    #[ORM\ManyToMany(targetEntity: Cours::class, inversedBy: 'danseurs')]
    private Collection $cours;

    #[ORM\ManyToOne(inversedBy: 'danseurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Foyer $foyer = null;

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(mappedBy: 'danseur', targetEntity: Inscription::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $inscriptions;

    // CONSTRUCTEUR UNIQUE ET FUSIONNÉ
    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
    }

    // Getters/Setters
    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self { $this->dateNaissance = $dateNaissance; return $this; }
    
    // Cours
    public function getCours(): Collection { return $this->cours; }
    public function addCours(Cours $cours): self { if (!$this->cours->contains($cours)) { $this->cours->add($cours); } return $this; }
    public function removeCours(Cours $cours): self { $this->cours->removeElement($cours); return $this; }

    // Foyer
    public function getFoyer(): ?Foyer { return $this->foyer; }
    public function setFoyer(?Foyer $foyer): static { $this->foyer = $foyer; return $this; }

    // Inscriptions
    public function getInscriptions(): Collection { return $this->inscriptions; }
    public function addInscription(Inscription $inscription): static {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setDanseur($this);
        }
        return $this;
    }
    public function removeInscription(Inscription $inscription): static {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getDanseur() === $this) { $inscription->setDanseur(null); }
        }
        return $this;
    }

    public function __toString(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')) ?: 'Danseur';
    }
}