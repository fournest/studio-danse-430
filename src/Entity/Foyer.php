<?php

namespace App\Entity;

use App\Repository\FoyerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoyerRepository::class)]
class Foyer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 255)]
    private ?string $ville = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactUrgence = null;

    #[ORM\OneToOne(inversedBy: 'foyer', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Champs du Parent 2
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $parent2Telephone = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $parent2IsDifferent = false;

    /** Remise exceptionnelle accordée par le bureau (en euros). */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $remiseManuelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motifRemise = null;

    /** Libellé unique de virement (ex. COTIS-2026-DUPONT). */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $referenceVirement = null;

    /**
     * @var Collection<int, Danseur>
     */
    #[ORM\OneToMany(targetEntity: Danseur::class, mappedBy: 'foyer', orphanRemoval: true)]
    private Collection $danseurs;

    public function __construct()
    {
        $this->danseurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getContactUrgence(): ?string
    {
        return $this->contactUrgence;
    }

    public function setContactUrgence(?string $contactUrgence): static
    {
        $this->contactUrgence = $contactUrgence;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, Danseur>
     */
    public function getDanseurs(): Collection
    {
        return $this->danseurs;
    }

    public function addDanseur(Danseur $danseur): static
    {
        if (!$this->danseurs->contains($danseur)) {
            $this->danseurs->add($danseur);
            $danseur->setFoyer($this);
        }
        return $this;
    }

    public function removeDanseur(Danseur $danseur): static
    {
        if ($this->danseurs->removeElement($danseur)) {
            if ($danseur->getFoyer() === $this) {
                $danseur->setFoyer(null);
            }
        }
        return $this;
    }

    // Getters / Setters pour Parent 2
    public function getParent2Nom(): ?string
    {
        return $this->parent2Nom;
    }

    public function setParent2Nom(?string $parent2Nom): static
    {
        $this->parent2Nom = $parent2Nom;
        return $this;
    }

    public function getParent2Prenom(): ?string
    {
        return $this->parent2Prenom;
    }

    public function setParent2Prenom(?string $parent2Prenom): static
    {
        $this->parent2Prenom = $parent2Prenom;
        return $this;
    }

    public function getParent2Email(): ?string
    {
        return $this->parent2Email;
    }

    public function setParent2Email(?string $parent2Email): static
    {
        $this->parent2Email = $parent2Email;
        return $this;
    }

    public function getParent2Telephone(): ?string
    {
        return $this->parent2Telephone;
    }

    public function setParent2Telephone(?string $parent2Telephone): static
    {
        $this->parent2Telephone = $parent2Telephone;
        return $this;
    }

    public function isParent2IsDifferent(): bool
    {
        return $this->parent2IsDifferent;
    }

    public function setParent2IsDifferent(bool $parent2IsDifferent): static
    {
        $this->parent2IsDifferent = $parent2IsDifferent;
        return $this;
    }

    public function getRemiseManuelle(): ?float
    {
        return null === $this->remiseManuelle ? null : (float) $this->remiseManuelle;
    }

    public function setRemiseManuelle(string|float|int|null $remiseManuelle): static
    {
        if (null === $remiseManuelle || '' === $remiseManuelle) {
            $this->remiseManuelle = null;
        } else {
            $this->remiseManuelle = number_format((float) $remiseManuelle, 2, '.', '');
        }

        return $this;
    }

    public function getMotifRemise(): ?string
    {
        return $this->motifRemise;
    }

    public function setMotifRemise(?string $motifRemise): static
    {
        $this->motifRemise = $motifRemise;
        return $this;
    }

    public function getReferenceVirement(): ?string
    {
        return $this->referenceVirement;
    }

    public function setReferenceVirement(?string $referenceVirement): static
    {
        $this->referenceVirement = $referenceVirement ? trim($referenceVirement) : null;

        return $this;
    }

    public function getParent2NomComplet(): ?string
    {
        if ($this->parent2Nom || $this->parent2Prenom) {
            return trim(($this->parent2Prenom ?? '') . ' ' . ($this->parent2Nom ?? ''));
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Foyer sans nom';
    }
}