<?php

namespace App\Entity;

use App\Enum\ModeLivraison;
use App\Enum\StatutReservation;
use App\Repository\ReservationCostumeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationCostumeRepository::class)]
class ReservationCostume
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Costume::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Costume $costume = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Foyer::class, inversedBy: 'reservationsCostumes')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Foyer $foyer = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $saison = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEvenement = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taille = null;

    #[ORM\Column]
    private int $quantite = 1;

    #[ORM\Column(type: Types::STRING, enumType: ModeLivraison::class)]
    private ModeLivraison $modeLivraison = ModeLivraison::RETRAIT_LOCAUX;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixTotal = null;

    #[ORM\Column(type: Types::STRING, enumType: StatutReservation::class)]
    private StatutReservation $statut = StatutReservation::EN_ATTENTE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarques = null;

    /** Mode de règlement souhaité pour la location (1× HelloAsso / chèque / espèces). */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $modePaiementSouhaite = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = StatutReservation::EN_ATTENTE;
        $this->modeLivraison = ModeLivraison::RETRAIT_LOCAUX;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCostume(): ?Costume
    {
        return $this->costume;
    }

    public function setCostume(?Costume $costume): static
    {
        $this->costume = $costume;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFoyer(): ?Foyer
    {
        return $this->foyer;
    }

    public function setFoyer(?Foyer $foyer): static
    {
        $this->foyer = $foyer;

        return $this;
    }

    public function getSaison(): ?string
    {
        return $this->saison;
    }

    public function setSaison(?string $saison): static
    {
        $this->saison = $saison;

        return $this;
    }

    public function getDateEvenement(): ?\DateTimeInterface
    {
        return $this->dateEvenement;
    }

    public function setDateEvenement(?\DateTimeInterface $dateEvenement): static
    {
        $this->dateEvenement = $dateEvenement;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille;
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getModeLivraison(): ModeLivraison
    {
        return $this->modeLivraison;
    }

    public function setModeLivraison(ModeLivraison $modeLivraison): static
    {
        $this->modeLivraison = $modeLivraison;
        return $this;
    }

    public function getPrixTotal(): ?string
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(string $prixTotal): static
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }

    public function getStatut(): StatutReservation
    {
        return $this->statut;
    }

    public function setStatut(StatutReservation $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getRemarques(): ?string
    {
        return $this->remarques;
    }

    public function setRemarques(?string $remarques): static
    {
        $this->remarques = $remarques;
        return $this;
    }

    public function getModePaiementSouhaite(): ?string
    {
        return $this->modePaiementSouhaite;
    }

    public function setModePaiementSouhaite(?string $modePaiementSouhaite): static
    {
        $this->modePaiementSouhaite = $modePaiementSouhaite ?: null;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}