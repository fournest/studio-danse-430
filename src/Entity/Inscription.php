<?php

namespace App\Entity;

use App\Enum\StatutInscription;
use App\Enum\StatutPaiement as StatutLignePaiement;
use App\Repository\InscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\ManyToOne(targetEntity: Cours::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cours $cours = null;

    /** Place non confirmée : hors cotisation tant qu'une place ne se libère pas. */
    #[ORM\Column(name: 'est_en_liste_d_attente', options: ['default' => false])]
    private bool $estEnListeDAttente = false;

    #[ORM\Column(length: 20)]
    private string $saison;

    #[ORM\Column(enumType: StatutDossier::class)]
    private StatutDossier $statutDossier;

    #[ORM\Column(enumType: StatutInscription::class, options: ['default' => 'brouillon'])]
    private StatutInscription $statut = StatutInscription::BROUILLON;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

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

    /** Montant net à payer pour cette inscription (après remises foyer). */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantTotal = null;

    /** @var Collection<int, Paiement> */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'inscription', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['dateEncaissementPrevue' => 'ASC', 'id' => 'ASC'])]
    private Collection $paiements;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastPiecesReminderSentAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastPaymentReminderSentAt = null;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->statut = StatutInscription::BROUILLON;
    }

    public function __toString(): string
    {
        $danseur = $this->danseur ? (string) $this->danseur : '?';
        $cours = $this->cours?->getNom() ?? '?';

        return sprintf('%s — %s (%s)', $danseur, $cours, $this->saison ?? '');
    }

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

    public function isEstEnListeDAttente(): bool
    {
        return $this->estEnListeDAttente;
    }

    public function setEstEnListeDAttente(bool $estEnListeDAttente): self
    {
        $this->estEnListeDAttente = $estEnListeDAttente;

        return $this;
    }

    /**
     * Passe une inscription de la liste d'attente vers une place confirmée.
     */
    public function confirmerDepuisListeAttente(): self
    {
        $this->estEnListeDAttente = false;

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

    public function getStatut(): StatutInscription
    {
        return $this->statut;
    }

    public function setStatut(StatutInscription $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeImmutable $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->statut->isEditable();
    }

    /**
     * Soumet l'inscription au bureau (fin du tunnel foyer).
     */
    public function soumettreAuBureau(): self
    {
        $this->statut = StatutInscription::EN_ATTENTE_VALIDATION;
        $this->dateValidation = new \DateTimeImmutable();
        $this->statutDossier = StatutDossier::EN_ATTENTE;

        return $this;
    }

    public function validerDefinitivement(): self
    {
        $this->statut = StatutInscription::VALIDE;
        $this->statutDossier = StatutDossier::VALIDE;

        return $this;
    }

    public function utiliseHelloAsso(): bool
    {
        foreach ($this->paiements as $paiement) {
            if ($paiement->getMode() === \App\Enum\ModePaiement::HELLOASSO) {
                return true;
            }
        }

        return str_contains(mb_strtolower((string) $this->modePaiement), 'helloasso');
    }

    public function utiliseVirement(): bool
    {
        foreach ($this->paiements as $paiement) {
            if ($paiement->getMode() === \App\Enum\ModePaiement::VIREMENT) {
                return true;
            }
        }

        return str_contains(mb_strtolower((string) $this->modePaiement), 'virement');
    }

    public function utiliseCheque(): bool
    {
        foreach ($this->paiements as $paiement) {
            if ($paiement->getMode() === \App\Enum\ModePaiement::CHEQUE) {
                return true;
            }
        }

        return str_contains(mb_strtolower((string) $this->modePaiement), 'chèque')
            || str_contains(mb_strtolower((string) $this->modePaiement), 'cheque');
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

    /**
     * Synchronise le champ legacy certificatMedical depuis l'état santé du danseur.
     */
    public function syncCertificatMedicalFromDanseur(): self
    {
        $danseur = $this->danseur;
        if (null === $danseur) {
            return $this;
        }

        if ($danseur->getCertificatFilename()) {
            $this->certificatMedical = $danseur->getCertificatFilename();

            return $this;
        }

        if ($danseur->isAttestationQsSportValide()) {
            $date = $danseur->getDateSignatureQsSport();
            $this->certificatMedical = $date
                ? sprintf('QS-Sport validé le %s', $date->format('d/m/Y'))
                : 'QS-Sport validé';

            return $this;
        }

        $this->certificatMedical = $danseur->getStatutSante()?->getLabel();

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

    public function getPayeurPrenom(): ?string
    {
        return $this->payeurPrenom;
    }

    public function setPayeurPrenom(?string $payeurPrenom): self
    {
        $this->payeurPrenom = $payeurPrenom;
        return $this;
    }

    public function getPayeurLabel(): string
    {
        $nom = trim((string) ($this->payeurNom ?? ''));
        $prenom = trim((string) ($this->payeurPrenom ?? ''));
        if ($nom !== '' || $prenom !== '') {
            return trim($prenom.' '.$nom);
        }

        $foyer = $this->danseur?->getFoyer();

        return $foyer?->getNom() ?? '—';
    }

    public function getPayeurEmail(): ?string
    {
        return $this->payeurEmail;
    }

    public function setPayeurEmail(?string $payeurEmail): self
    {
        $this->payeurEmail = $payeurEmail;
        return $this;
    }

    public function isDemandeFactureCE(): bool
    {
        return $this->demandeFactureCE;
    }

    public function setDemandeFactureCE(bool $demandeFactureCE): self
    {
        $this->demandeFactureCE = $demandeFactureCE;
        return $this;
    }

    public function getNomEntrepriseCE(): ?string
    {
        return $this->nomEntrepriseCE;
    }

    public function setNomEntrepriseCE(?string $nomEntrepriseCE): self
    {
        $this->nomEntrepriseCE = $nomEntrepriseCE;
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

    public function getMontantTotal(): ?float
    {
        return null === $this->montantTotal ? null : (float) $this->montantTotal;
    }

    public function setMontantTotal(string|float|int|null $montantTotal): self
    {
        if (null === $montantTotal || '' === $montantTotal) {
            $this->montantTotal = null;
        } else {
            $this->montantTotal = number_format((float) $montantTotal, 2, '.', '');
        }

        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): self
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setInscription($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): self
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getInscription() === $this) {
                $paiement->setInscription(null);
            }
        }

        return $this;
    }

    public function clearPaiements(): self
    {
        foreach ($this->paiements->toArray() as $paiement) {
            $this->removePaiement($paiement);
        }

        return $this;
    }

    /**
     * Somme des paiements encaissés (confirmés par la trésorerie).
     */
    public function getMontantEncaisse(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $paiement) {
            if ($paiement->isPaid()) {
                $total += $paiement->getMontant();
            }
        }

        return round($total, 2);
    }

    /**
     * Somme des paiements déclarés par la famille (en attente de validation trésorerie).
     */
    public function getMontantDeclare(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $paiement) {
            if ($paiement->isDeclared()) {
                $total += $paiement->getMontant();
            }
        }

        return round($total, 2);
    }

    /**
     * @deprecated Alias — utilise getMontantEncaisse()
     */
    public function getMontantRegle(): float
    {
        return $this->getMontantEncaisse();
    }

    /**
     * Somme de toutes les lignes de paiement planifiées (hors refusées).
     */
    public function getMontantPlanifie(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $paiement) {
            if ($paiement->getStatut() !== StatutLignePaiement::REFUSE) {
                $total += $paiement->getMontant();
            }
        }

        return round($total, 2);
    }

    public function getResteAPayer(): float
    {
        return round(max(0.0, ($this->getMontantTotal() ?? 0.0) - $this->getMontantEncaisse()), 2);
    }

    /**
     * Reste à payer après déduction des montants déclarés (indicateur d'alerte allégé).
     */
    public function getResteAPayerApresDeclaration(): float
    {
        return round(max(0.0, $this->getResteAPayer() - $this->getMontantDeclare()), 2);
    }

    /**
     * Libellé texte du reste à payer (pour TextField EasyAdmin).
     */
    public function getResteAPayerLabel(): string
    {
        return number_format($this->getResteAPayer(), 2, ',', ' ').' €';
    }

    /**
     * Résumé lisible des échéances de règlement (ex. « 3/10 réglés »).
     */
    public function getEcheances(): string
    {
        $total = $this->paiements->count();
        if ($total === 0) {
            return 'Aucune échéance';
        }

        $regles = 0;
        foreach ($this->paiements as $paiement) {
            if ($paiement->isPaid()) {
                ++$regles;
            }
        }

        return sprintf('%d/%d réglés', $regles, $total);
    }

    public function getEcheancesCount(): int
    {
        return $this->paiements->count();
    }

    /**
     * Met à jour le statut global (Non payé / Partiel / Soldé) selon les encaissements.
     * Un plan de règlement soumis (chèques/virements en attente) n’est plus « Non payé ».
     */
    public function refreshStatutPaiement(): self
    {
        $total = $this->getMontantTotal() ?? 0.0;
        $regle = $this->getMontantRegle();
        $planifie = $this->getMontantPlanifie();

        if ($total <= 0.001) {
            $this->statutPaiement = $this->paiements->isEmpty()
                ? StatutPaiement::NON_PAYE
                : StatutPaiement::SOLDE;
        } elseif ($regle + 0.001 >= $total) {
            $this->statutPaiement = StatutPaiement::SOLDE;
        } elseif ($regle > 0.001 || $planifie > 0.001) {
            $this->statutPaiement = StatutPaiement::PARTIEL;
        } else {
            $this->statutPaiement = StatutPaiement::NON_PAYE;
        }

        return $this;
    }

    public function hasPlanReglement(): bool
    {
        return !$this->paiements->isEmpty();
    }

    /**
     * Plan soumis, en attente de validation / encaissement par le trésorier.
     */
    public function isEnAttenteEncaissement(): bool
    {
        if (!$this->hasPlanReglement()) {
            return false;
        }

        if ($this->statutPaiement === StatutPaiement::SOLDE) {
            return false;
        }

        return $this->getMontantRegle() + 0.001 < ($this->getMontantTotal() ?? 0.0);
    }

    /**
     * Libellé affiché côté espace familial (plus précis que la valeur brute de l’enum).
     */
    public function getLibelleStatutPaiement(): string
    {
        if ($this->statutPaiement === StatutPaiement::SOLDE) {
            return 'Soldé';
        }

        if ($this->isEnAttenteEncaissement()) {
            return 'En attente d\'encaissement';
        }

        return $this->statutPaiement->value;
    }

    public function getIndicateurReglement(): string
    {
        $total = $this->getMontantTotal() ?? 0.0;
        $regle = $this->getMontantRegle();
        $reste = $this->getResteAPayer();

        return sprintf(
            'Total %s € · Réglé %s € · Reste %s € · %s',
            number_format($total, 2, ',', ' '),
            number_format($regle, 2, ',', ' '),
            number_format($reste, 2, ',', ' '),
            $this->getLibelleStatutPaiement()
        );
    }

    public function getLastPiecesReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->lastPiecesReminderSentAt;
    }

    public function setLastPiecesReminderSentAt(?\DateTimeImmutable $lastPiecesReminderSentAt): self
    {
        $this->lastPiecesReminderSentAt = $lastPiecesReminderSentAt;

        return $this;
    }

    public function getLastPaymentReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->lastPaymentReminderSentAt;
    }

    public function setLastPaymentReminderSentAt(?\DateTimeImmutable $lastPaymentReminderSentAt): self
    {
        $this->lastPaymentReminderSentAt = $lastPaymentReminderSentAt;

        return $this;
    }

    public function hasOverduePaiement(): bool
    {
        return $this->getOverduePaiements() !== [];
    }

    /**
     * @return list<Paiement>
     */
    public function getOverduePaiements(): array
    {
        $overdue = [];
        foreach ($this->paiements as $paiement) {
            if ($paiement->isOverdue()) {
                $overdue[] = $paiement;
            }
        }

        return $overdue;
    }

    /**
     * @return list<string>
     */
    public function getPiecesManquantes(): array
    {
        $pieces = [];
        $danseur = $this->danseur;

        if (null === $danseur) {
            return ['Dossier danseur introuvable'];
        }

        if (!$danseur->hasJustificatifSanteComplet()) {
            $pieces[] = 'Justificatif de santé (certificat médical ou questionnaire QS-Sport signé)';
        }

        if ($this->statutDossier === StatutDossier::INCOMPLET) {
            $pieces[] = 'Éléments du dossier d\'inscription signalés comme incomplets par le bureau';
        }

        if ($pieces === []) {
            $pieces[] = 'Compléter le dossier depuis votre Espace Famille';
        }

        return $pieces;
    }
}
