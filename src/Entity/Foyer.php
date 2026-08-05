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

    /**
     * @var Collection<int, AchatGoodie>
     */
    #[ORM\OneToMany(targetEntity: AchatGoodie::class, mappedBy: 'foyer', orphanRemoval: true, cascade: ['persist'])]
    private Collection $achatsGoodies;

    /**
     * @var Collection<int, ReservationCostume>
     */
    #[ORM\OneToMany(targetEntity: ReservationCostume::class, mappedBy: 'foyer', cascade: ['persist'])]
    private Collection $reservationsCostumes;

    public function __construct()
    {
        $this->danseurs = new ArrayCollection();
        $this->achatsGoodies = new ArrayCollection();
        $this->reservationsCostumes = new ArrayCollection();
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

    /**
     * @return Collection<int, AchatGoodie>
     */
    public function getAchatsGoodies(): Collection
    {
        return $this->achatsGoodies;
    }

    public function addAchatGoodie(AchatGoodie $achatGoodie): static
    {
        if (!$this->achatsGoodies->contains($achatGoodie)) {
            $this->achatsGoodies->add($achatGoodie);
            $achatGoodie->setFoyer($this);
        }

        return $this;
    }

    public function removeAchatGoodie(AchatGoodie $achatGoodie): static
    {
        if ($this->achatsGoodies->removeElement($achatGoodie)) {
            if ($achatGoodie->getFoyer() === $this) {
                $achatGoodie->setFoyer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ReservationCostume>
     */
    public function getReservationsCostumes(): Collection
    {
        return $this->reservationsCostumes;
    }

    public function addReservationCostume(ReservationCostume $reservationCostume): static
    {
        if (!$this->reservationsCostumes->contains($reservationCostume)) {
            $this->reservationsCostumes->add($reservationCostume);
            $reservationCostume->setFoyer($this);
        }

        return $this;
    }

    public function removeReservationCostume(ReservationCostume $reservationCostume): static
    {
        if ($this->reservationsCostumes->removeElement($reservationCostume)) {
            if ($reservationCostume->getFoyer() === $this) {
                $reservationCostume->setFoyer(null);
            }
        }

        return $this;
    }

    /**
     * Inscriptions des danseurs rattachés à CE foyer (foyer payeur).
     * Ne remonte jamais les inscriptions d’un enfant consulté en co-parent
     * depuis un autre foyer.
     *
     * @return list<Inscription>
     */
    public function getInscriptions(?string $saison = null): array
    {
        $inscriptions = [];

        foreach ($this->danseurs as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if (null !== $saison && $inscription->getSaison() !== $saison) {
                    continue;
                }
                $inscriptions[] = $inscription;
            }
        }

        return $inscriptions;
    }

    public function getTotalDu(?string $saison = '2026/2027'): float
    {
        $total = 0.0;
        foreach ($this->getInscriptions($saison) as $inscription) {
            $total += $inscription->getMontantTotal() ?? 0.0;
        }

        return round($total, 2);
    }

    public function getTotalEncaisse(?string $saison = '2026/2027'): float
    {
        $total = 0.0;
        foreach ($this->getInscriptions($saison) as $inscription) {
            $total += $inscription->getMontantEncaisse();
        }

        return round($total, 2);
    }

    public function getTotalDeclare(?string $saison = '2026/2027'): float
    {
        $total = 0.0;
        foreach ($this->getInscriptions($saison) as $inscription) {
            $total += $inscription->getMontantDeclare();
        }

        return round($total, 2);
    }

    public function getResteAPayer(?string $saison = '2026/2027'): float
    {
        return round(max(0.0, $this->getTotalDu($saison) - $this->getTotalEncaisse($saison)), 2);
    }

    public function getResteAPayerApresDeclaration(?string $saison = '2026/2027'): float
    {
        return round(max(0.0, $this->getResteAPayer($saison) - $this->getTotalDeclare($saison)), 2);
    }

    public function getTotalDuSaison(): float
    {
        return $this->getTotalDu();
    }

    public function getTotalEncaisseSaison(): float
    {
        return $this->getTotalEncaisse();
    }

    public function getTotalDeclareSaison(): float
    {
        return $this->getTotalDeclare();
    }

    public function getResteAPayerSaison(): float
    {
        return $this->getResteAPayer();
    }

    public function getResteAPayerBadgeSaisonLabel(): string
    {
        return number_format($this->getResteAPayerSaison(), 2, ',', ' ').' €';
    }

    public function getTotalDeclareSaisonLabel(): string
    {
        return number_format($this->getTotalDeclareSaison(), 2, ',', ' ').' €';
    }

    /**
     * @return list<Paiement>
     */
    public function getPaiementsDeclares(?string $saison = '2026/2027'): array
    {
        $declares = [];
        foreach ($this->getInscriptions($saison) as $inscription) {
            foreach ($inscription->getPaiements() as $paiement) {
                if ($paiement->isDeclared()) {
                    $declares[] = $paiement;
                }
            }
        }

        return $declares;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Foyer sans nom';
    }
}