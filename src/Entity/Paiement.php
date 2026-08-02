<?php

namespace App\Entity;

use App\Enum\ModePaiement;
use App\Enum\StatutPaiement as StatutLignePaiement;
use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Inscription::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Inscription $inscription = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $montant = '0.00';

    #[ORM\Column(enumType: ModePaiement::class)]
    private ModePaiement $mode = ModePaiement::CHEQUE;

    #[ORM\Column(enumType: StatutLignePaiement::class)]
    private StatutLignePaiement $statut = StatutLignePaiement::EN_ATTENTE;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emetteur = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateEncaissementPrevue = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateEncaissementReelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarques = null;

    public function __toString(): string
    {
        return sprintf(
            '%s — %s € (%s)',
            $this->mode->getLabel(),
            number_format((float) $this->montant, 2, ',', ' '),
            $this->statut->getLabel()
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInscription(): ?Inscription
    {
        return $this->inscription;
    }

    public function setInscription(?Inscription $inscription): self
    {
        $this->inscription = $inscription;

        return $this;
    }

    public function getMontant(): float
    {
        return (float) $this->montant;
    }

    public function setMontant(string|float|int $montant): self
    {
        $this->montant = number_format((float) $montant, 2, '.', '');

        return $this;
    }

    public function getMode(): ModePaiement
    {
        return $this->mode;
    }

    public function setMode(ModePaiement $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getStatut(): StatutLignePaiement
    {
        return $this->statut;
    }

    public function setStatut(StatutLignePaiement $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getEmetteur(): ?string
    {
        return $this->emetteur;
    }

    public function setEmetteur(?string $emetteur): self
    {
        $this->emetteur = $emetteur;

        return $this;
    }

    public function getDateEncaissementPrevue(): ?\DateTimeImmutable
    {
        return $this->dateEncaissementPrevue;
    }

    public function setDateEncaissementPrevue(?\DateTimeImmutable $dateEncaissementPrevue): self
    {
        $this->dateEncaissementPrevue = $dateEncaissementPrevue;

        return $this;
    }

    public function getDateEncaissementReelle(): ?\DateTimeImmutable
    {
        return $this->dateEncaissementReelle;
    }

    public function setDateEncaissementReelle(?\DateTimeImmutable $dateEncaissementReelle): self
    {
        $this->dateEncaissementReelle = $dateEncaissementReelle;

        return $this;
    }

    public function getRemarques(): ?string
    {
        return $this->remarques;
    }

    public function setRemarques(?string $remarques): self
    {
        $this->remarques = $remarques;

        return $this;
    }

    public function marquerEncaisse(?\DateTimeImmutable $date = null): self
    {
        $this->statut = StatutLignePaiement::ENCAISSE;
        $this->dateEncaissementReelle = $date ?? new \DateTimeImmutable('today');

        return $this;
    }
}
