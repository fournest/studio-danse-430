<?php
namespace App\Entity;

use App\Enum\StatutInscription;
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

    /** Numéro / libellé de groupe pour différencier les cours de même discipline (ex. « 1 », « Ado 1 »). */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $numeroGroupe = null;

    #[ORM\Column(length: 10)]
    private string $jour;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $heure;

    #[ORM\Column(length: 50)]
    private string $professeur;

    #[ORM\Column(options: ['default' => 25])]
    private int $capaciteMax = 25;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whatsappGroupLink = null;

    /** Durée du créneau en minutes (60, 75, 90). */
    #[ORM\Column]
    private int $dureeMinutes = 90;

    /** Tarif saison (en euros) — grille modifiable en admin. */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $tarif = '0.00';

    /** Âge minimal éligible en années révolues (null = pas de borne basse). */
    #[ORM\Column(nullable: true)]
    private ?int $ageMin = null;

    /** Âge maximal éligible en années révolues (null = pas de borne haute). */
    #[ORM\Column(nullable: true)]
    private ?int $ageMax = null;

    /** Année de naissance minimale éligible (null = pas de borne basse) — legacy / complément. */
    #[ORM\Column(nullable: true)]
    private ?int $anneeNaissanceMin = null;

    /** Année de naissance maximale éligible (null = pas de borne haute) — legacy / complément. */
    #[ORM\Column(nullable: true)]
    private ?int $anneeNaissanceMax = null;

    /**
     * @var Collection<int, Danseur>
     */
    #[ORM\ManyToMany(targetEntity: Danseur::class, mappedBy: 'cours')]
    private Collection $danseurs;

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Inscription::class)]
    private Collection $inscriptions;

    public function __construct()
    {
        $this->danseurs = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getNumeroGroupe(): ?string
    {
        return $this->numeroGroupe;
    }

    public function setNumeroGroupe(?string $numeroGroupe): self
    {
        $trimmed = null !== $numeroGroupe ? trim($numeroGroupe) : null;
        $this->numeroGroupe = ($trimmed === '') ? null : $trimmed;

        return $this;
    }

    /**
     * Nom d’affichage avec numéro / groupe : « Modern Jazz #1 » ou « Éveil Danse - Groupe 2 ».
     */
    public function getNomComplet(): string
    {
        $groupe = $this->numeroGroupe;
        if (null === $groupe || $groupe === '') {
            return $this->nom;
        }

        if (preg_match('/^\d+[A-Za-z]?$/u', $groupe)) {
            return sprintf('%s #%s', $this->nom, $groupe);
        }

        return sprintf('%s - %s', $this->nom, $groupe);
    }

    public function getJour(): string
    {
        return $this->jour;
    }

    public function setJour(string $jour): self
    {
        $this->jour = $jour;
        return $this;
    }

    public function getHeure(): \DateTimeInterface
    {
        return $this->heure;
    }

    public function setHeure(\DateTimeInterface $heure): self
    {
        $this->heure = $heure;
        return $this;
    }

    public function getProfesseur(): string
    {
        return $this->professeur;
    }

    public function setProfesseur(string $professeur): self
    {
        $this->professeur = $professeur;
        return $this;
    }

    /**
     * Noms des professeurs prêts pour l’affichage (jamais d’email brut).
     * Accepte plusieurs valeurs séparées par virgule, point-virgule ou « / ».
     * Un email éventuel est transformé en libellé (partie avant @).
     *
     * @return list<string>
     */
    public function getProfesseursNoms(): array
    {
        $parts = preg_split('/[,;\/|]+/', $this->professeur) ?: [];
        $noms = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '@')) {
                if (preg_match('/^(.+?)\s*[<\(]\s*([^@\s]+@[^>\)]+)\s*[>\)]\s*$/u', $part, $m)) {
                    $part = trim($m[1]);
                } else {
                    $local = (string) strstr($part, '@', true);
                    $part = ucwords(str_replace(['.', '_', '-'], ' ', $local));
                }
            }

            if ($part !== '') {
                $noms[] = $part;
            }
        }

        return array_values(array_unique($noms));
    }

    /** Libellé d’affichage : « Marie Dupont · Jean Martin ». */
    public function getProfesseurLabel(): string
    {
        return implode(' · ', $this->getProfesseursNoms());
    }

    /**
     * Prénom(s) des professeurs pour l’affichage public (ex. « Élodie » ou « Élodie · Marie »).
     */
    public function getProfesseurPrenom(): string
    {
        $prenoms = [];
        foreach ($this->getProfesseursNoms() as $nomComplet) {
            $parts = preg_split('/\s+/u', $nomComplet, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $prenom = $parts[0] ?? $nomComplet;
            if ($prenom !== '') {
                $prenoms[] = $prenom;
            }
        }

        return $prenoms !== [] ? implode(' · ', $prenoms) : 'la professeure';
    }

    public function getCapaciteMax(): int
    {
        return $this->capaciteMax;
    }

    public function setCapaciteMax(int $capaciteMax): self
    {
        $this->capaciteMax = $capaciteMax;
        return $this;
    }

    public function getWhatsAppGroupLink(): ?string
    {
        return $this->whatsappGroupLink;
    }

    public function setWhatsAppGroupLink(?string $whatsappGroupLink): self
    {
        $this->whatsappGroupLink = $whatsappGroupLink;
        return $this;
    }

    public function getDureeMinutes(): int
    {
        return $this->dureeMinutes;
    }

    public function setDureeMinutes(int $dureeMinutes): self
    {
        $this->dureeMinutes = $dureeMinutes;
        return $this;
    }

    public function getTarif(): string
    {
        return $this->tarif;
    }

    public function setTarif(string|float|int $tarif): self
    {
        $this->tarif = number_format((float) $tarif, 2, '.', '');
        return $this;
    }

    public function getAgeMin(): ?int
    {
        return $this->ageMin;
    }

    public function setAgeMin(?int $ageMin): self
    {
        $this->ageMin = $ageMin;
        return $this;
    }

    public function getAgeMax(): ?int
    {
        return $this->ageMax;
    }

    public function setAgeMax(?int $ageMax): self
    {
        $this->ageMax = $ageMax;
        return $this;
    }

    public function getAnneeNaissanceMin(): ?int
    {
        return $this->anneeNaissanceMin;
    }

    public function setAnneeNaissanceMin(?int $anneeNaissanceMin): self
    {
        $this->anneeNaissanceMin = $anneeNaissanceMin;
        return $this;
    }

    public function getAnneeNaissanceMax(): ?int
    {
        return $this->anneeNaissanceMax;
    }

    public function setAnneeNaissanceMax(?int $anneeNaissanceMax): self
    {
        $this->anneeNaissanceMax = $anneeNaissanceMax;
        return $this;
    }

    public function getDureeLabel(): string
    {
        return match ($this->dureeMinutes) {
            60 => '1h00',
            75 => '1h15',
            90 => '1h30',
            default => sprintf('%d min', $this->dureeMinutes),
        };
    }

    public function getHeureFin(): \DateTimeInterface
    {
        $debut = \DateTimeImmutable::createFromInterface($this->heure);

        return $debut->modify(sprintf('+%d minutes', $this->dureeMinutes));
    }

    /** Libellé « 18h30 - 20h00 ». */
    public function getHoraireLabel(): string
    {
        return sprintf(
            '%s - %s',
            $this->heure->format('G\\hi'),
            $this->getHeureFin()->format('G\\hi')
        );
    }

    /**
     * Libellé de tranche d’âge (ex. « 8-12 ans ») ou null si aucune borne.
     */
    public function getTrancheAgeLabel(): ?string
    {
        if (null === $this->ageMin && null === $this->ageMax) {
            return null;
        }

        if (null !== $this->ageMin && null !== $this->ageMax) {
            return sprintf('%d-%d ans', $this->ageMin, $this->ageMax);
        }

        if (null !== $this->ageMin) {
            return sprintf('à partir de %d ans', $this->ageMin);
        }

        return sprintf('jusqu’à %d ans', $this->ageMax);
    }

    /**
     * Libellé « Réservé aux … » pour l’UI tunnel (âge ou années de naissance).
     */
    public function getReservationAgeLabel(): ?string
    {
        if (null !== $this->getTrancheAgeLabel()) {
            return 'Réservé aux ' . $this->getTrancheAgeLabel();
        }

        if (null === $this->anneeNaissanceMin && null === $this->anneeNaissanceMax) {
            return null;
        }

        if (null !== $this->anneeNaissanceMin && null !== $this->anneeNaissanceMax) {
            return sprintf('Réservé aux né(e)s %d–%d', $this->anneeNaissanceMin, $this->anneeNaissanceMax);
        }

        if (null !== $this->anneeNaissanceMin) {
            return sprintf('Réservé aux né(e)s à partir de %d', $this->anneeNaissanceMin);
        }

        return sprintf('Réservé aux né(e)s jusqu’à %d', $this->anneeNaissanceMax);
    }

    public function hasAgeBounds(): bool
    {
        return null !== $this->ageMin || null !== $this->ageMax;
    }

    public function hasBirthYearBounds(): bool
    {
        return null !== $this->anneeNaissanceMin || null !== $this->anneeNaissanceMax;
    }

    /**
     * Aucune borne d’âge ni d’année de naissance → cours ouvert à tous.
     */
    public function isOpenToAllAges(): bool
    {
        return !$this->hasAgeBounds() && !$this->hasBirthYearBounds();
    }

    /**
     * Indique si l’âge (années révolues) est dans la tranche [ageMin, ageMax].
     * Bornes nulles = pas de contrainte sur ce critère.
     */
    public function isEligibleForAge(?int $age): bool
    {
        if (!$this->hasAgeBounds()) {
            return true;
        }

        if (null === $age) {
            return false;
        }

        if (null !== $this->ageMin && $age < $this->ageMin) {
            return false;
        }

        if (null !== $this->ageMax && $age > $this->ageMax) {
            return false;
        }

        return true;
    }

    /**
     * Indique si le danseur (année de naissance) est éligible à ce créneau.
     * Bornes nulles = pas de contrainte sur ce critère.
     */
    public function isEligibleForBirthYear(?int $anneeNaissance): bool
    {
        if (!$this->hasBirthYearBounds()) {
            return true;
        }

        if (null === $anneeNaissance) {
            return false;
        }

        if (null !== $this->anneeNaissanceMin && $anneeNaissance < $this->anneeNaissanceMin) {
            return false;
        }

        if (null !== $this->anneeNaissanceMax && $anneeNaissance > $this->anneeNaissanceMax) {
            return false;
        }

        return true;
    }

    /**
     * Compatibilité complète (âge révolu prioritaire, sinon année de naissance).
     * Sans bornes → toujours compatible.
     */
    public function isEligibleForDanseur(Danseur $danseur): bool
    {
        return $this->isCompatibleAvecDanseur($danseur);
    }

    public function isCompatibleAvecDanseur(Danseur $danseur): bool
    {
        if ($this->isOpenToAllAges()) {
            return true;
        }

        if ($this->hasAgeBounds()) {
            $age = $danseur->getAge();
            if (null !== $age) {
                return $this->isEligibleForAge($age);
            }
            // Âge inconnu : bascule sur l’année de naissance si disponible.
            if (null !== $danseur->getAnneeNaissance()) {
                return $this->isEligibleForBirthYear($danseur->getAnneeNaissance());
            }

            return false;
        }

        return $this->isEligibleForBirthYear($danseur->getAnneeNaissance());
    }

    public function isCompatibleAvecDateNaissance(\DateTimeInterface $dateNaissance): bool
    {
        if ($this->isOpenToAllAges()) {
            return true;
        }

        $naissance = \DateTimeImmutable::createFromInterface($dateNaissance);

        if ($this->hasAgeBounds()) {
            $age = (new \DateTimeImmutable('today'))->diff($naissance)->y;

            return $this->isEligibleForAge($age);
        }

        return $this->isEligibleForBirthYear((int) $naissance->format('Y'));
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

    /**
     * @return Collection<int, Inscription>
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    /**
     * Adhérents occupant une place (hors liste d'attente / annulés).
     * Compte EN_ATTENTE_VALIDATION et VALIDE ; inclut aussi BROUILLON (place provisoire tunnel).
     */
    public function getNombreInscrits(?string $saison = null): int
    {
        $n = 0;
        foreach ($this->inscriptions as $inscription) {
            if ($saison !== null && $inscription->getSaison() !== $saison) {
                continue;
            }
            if ($inscription->isEstEnListeDAttente()) {
                continue;
            }
            if ($inscription->getStatut() === StatutInscription::ANNULE) {
                continue;
            }
            if (\in_array($inscription->getStatut(), [
                StatutInscription::BROUILLON,
                StatutInscription::EN_ATTENTE_VALIDATION,
                StatutInscription::VALIDE,
            ], true)) {
                ++$n;
            }
        }

        return $n;
    }

    public function getPlacesRestantes(?string $saison = null): int
    {
        return max(0, $this->capaciteMax - $this->getNombreInscrits($saison));
    }

    public function estComplet(?string $saison = null): bool
    {
        return $this->getPlacesRestantes($saison) <= 0;
    }

    /**
     * Libellé jauge admin : « 18 / 25 élèves ».
     */
    public function getRemplissageLabel(?string $saison = null): string
    {
        return sprintf('%d / %d élèves', $this->getNombreInscrits($saison), $this->capaciteMax);
    }

    /** Libellé index EasyAdmin (toutes saisons / collection chargée). */
    public function getListeAttenteResume(): string
    {
        $n = \count($this->getInscriptionsListeAttente());

        return $n > 0 ? sprintf('%d élève(s)', $n) : '—';
    }

    /**
     * @return list<Inscription>
     */
    public function getInscriptionsListeAttente(?string $saison = null): array
    {
        $items = [];
        foreach ($this->inscriptions as $inscription) {
            if (!$inscription->isEstEnListeDAttente()) {
                continue;
            }
            if ($saison !== null && $inscription->getSaison() !== $saison) {
                continue;
            }
            if ($inscription->getStatut() === StatutInscription::ANNULE) {
                continue;
            }
            $items[] = $inscription;
        }

        return $items;
    }

    public function __toString(): string
    {
        $label = $this->getNomComplet();

        return $label !== '' ? $label : ('Cours #' . ($this->id ?? '?'));
    }
}
