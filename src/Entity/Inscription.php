<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Danseur::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Danseur $danseur = null;

    #[ORM\ManyToOne(targetEntity: Cours::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cours $cours = null;

    #[ORM\Column(length: 20)]
    private string $saison;

    #[ORM\Column(enumType: StatutDossier::class)]
    private StatutDossier $statutDossier;

    #[ORM\Column(nullable: true)]
    private ?string $certificatMedical = null;

    #[ORM\Column(enumType: StatutPaiement::class)]
    private StatutPaiement $statutPaiement;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $modePaiement = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $helloAssoPaymentId = null;

    // Gestion du Payeur par Inscription
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payeurNom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payeurPrenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payeurEmail = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $demandeFactureCE = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomEntrepriseCE = null;

    /** Remise exceptionnelle bureau sur cette inscription (en euros). */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $remiseManuelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motifRemise = null;

    // Getters/Setters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getDanseur(): ?Danseur
    {
        return $this->danseur;
    }
    public function setDanseur(?Danseur $danseur): self
    {
        $this->danseur = $danseur;
        return $this;
    }
    public function getCours(): ?Cours
    {
        return $this->cours;
    }
    public function setCours(?Cours $cours): self
    {
        $this->cours = $cours;
        return $this;
    }
    public function getSaison(): string
    {
        return $this->saison;
    }
    public function setSaison(string $saison): self
    {
        $this->saison = $saison;
        return $this;
    }
    public function getStatutDossier(): StatutDossier
    {
        return $this->statutDossier;
    }
    public function setStatutDossier(StatutDossier $statutDossier): self
    {
        $this->statutDossier = $statutDossier;
        return $this;
    }
    public function getCertificatMedical(): ?string
    {
        return $this->certificatMedical;
    }
    public function setCertificatMedical(?string $certificatMedical): self
    {
        $this->certificatMedical = $certificatMedical;
        return $this;
    }
    public function getStatutPaiement(): StatutPaiement
    {
        return $this->statutPaiement;
    }
    public function setStatutPaiement(StatutPaiement $statutPaiement): self
    {
        $this->statutPaiement = $statutPaiement;
        return $this;
    }
    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }
    public function setModePaiement(?string $modePaiement): self
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }
    public function getHelloAssoPaymentId(): ?string
    {
        return $this->helloAssoPaymentId;
    }
    public function setHelloAssoPaymentId(?string $helloAssoPaymentId): self
    {
        $this->helloAssoPaymentId = $helloAssoPaymentId;
        return $this;
    }
    public function getPayeurNom(): ?string
    {
        return $this->payeurNom;
    }
    public function setPayeurNom(?string $payeurNom): self
    {
        $this->payeurNom = $payeurNom;
        return $this;
    }

    public function getRemiseManuelle(): ?float
    {
        return null === $this->remiseManuelle ? null : (float) $this->remiseManuelle;
    }

    public function setRemiseManuelle(string|float|int|null $remiseManuelle): self
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

    public function setMotifRemise(?string $motifRemise): self
    {
        $this->motifRemise = $motifRemise;
        return $this;
    }
}
