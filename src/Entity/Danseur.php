<?php

namespace App\Entity;

use App\Repository\DanseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Cours;
use App\Entity\Inscription;
use App\Entity\Foyer;

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

    // Champs optionnels si le 2e parent diffère de celui du Foyer
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Prenom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $parent2Telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Email = null;

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

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
    }

    // Méthodes de fallback sur le Foyer
    public function getParent2NomComplet(): ?string
    {
        if ($this->parent2Nom || $this->parent2Prenom) {
            return trim(($this->parent2Prenom ?? '') . ' ' . ($this->parent2Nom ?? ''));
        }
        return $this->foyer?->getParent2NomComplet();
    }

    public function getParent2EmailEffectif(): ?string
    {
        return $this->parent2Email ?: $this->foyer?->getParent2Email();
    }

    public function getParent2TelephoneEffectif(): ?string
    {
        return $this->parent2Telephone ?: $this->foyer?->getParent2Telephone();
    }

    // Getters & Setters principaux
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getAnneeNaissance(): ?int
    {
        if (null === $this->dateNaissance) {
            return null;
        }

        return (int) $this->dateNaissance->format('Y');
    }

    // Getters & Setters du Parent 2 spécifique
    public function getParent2Nom(): ?string
    {
        return $this->parent2Nom;
    }

    public function setParent2Nom(?string $parent2Nom): self
    {
        $this->parent2Nom = $parent2Nom;
        return $this;
    }

    public function getParent2Prenom(): ?string
    {
        return $this->parent2Prenom;
    }

    public function setParent2Prenom(?string $parent2Prenom): self
    {
        $this->parent2Prenom = $parent2Prenom;
        return $this;
    }

    public function getParent2Telephone(): ?string
    {
        return $this->parent2Telephone;
    }

    public function setParent2Telephone(?string $parent2Telephone): self
    {
        $this->parent2Telephone = $parent2Telephone;
        return $this;
    }

    public function getParent2Email(): ?string
    {
        return $this->parent2Email;
    }

    public function setParent2Email(?string $parent2Email): self
    {
        $this->parent2Email = $parent2Email;
        return $this;
    }

    // Cours
    public function getCours(): Collection
    {
        return $this->cours;
    }

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

    // Foyer
    public function getFoyer(): ?Foyer
    {
        return $this->foyer;
    }

    public function setFoyer(?Foyer $foyer): static
    {
        $this->foyer = $foyer;
        return $this;
    }

    // Inscriptions
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setDanseur($this);
        }
        return $this;
    }

    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getDanseur() === $this) {
                $inscription->setDanseur(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')) ?: 'Danseur';
    }
}