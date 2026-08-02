<?php

namespace App\Entity;

use App\Enum\StatutSante;
use App\Repository\DanseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: DanseurRepository::class)]
#[Vich\Uploadable]
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Prenom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $parent2Telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parent2Email = null;

    /** @var Collection<int, Cours> */
    #[ORM\ManyToMany(targetEntity: Cours::class, inversedBy: 'danseurs')]
    private Collection $cours;

    #[ORM\ManyToOne(inversedBy: 'danseurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Foyer $foyer = null;

    /** @var Collection<int, Inscription> */
    #[ORM\OneToMany(mappedBy: 'danseur', targetEntity: Inscription::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $inscriptions;

    #[ORM\Column(enumType: StatutSante::class, options: ['default' => 'en_attente'])]
    private StatutSante $statutSante = StatutSante::EN_ATTENTE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificatFilename = null;

    #[Vich\UploadableField(mapping: 'certificats_medicaux', fileNameProperty: 'certificatFilename')]
    private ?File $certificatFile = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $attestationQsSportValide = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateSignatureQsSport = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarqueSante = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
    }

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

    /** Mineur si âge exact < 18 ans à la date de référence (défaut : aujourd'hui). */
    public function isMineur(?\DateTimeInterface $reference = null): bool
    {
        if (null === $this->dateNaissance) {
            return true;
        }

        $ref = $reference instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($reference)
            : new \DateTimeImmutable('today');

        $naissance = \DateTimeImmutable::createFromInterface($this->dateNaissance);

        return $naissance->diff($ref)->y < 18;
    }

    public function isMajeur(?\DateTimeInterface $reference = null): bool
    {
        return !$this->isMineur($reference);
    }

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

    public function getFoyer(): ?Foyer
    {
        return $this->foyer;
    }

    public function setFoyer(?Foyer $foyer): static
    {
        $this->foyer = $foyer;

        return $this;
    }

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

    public function getStatutSante(): StatutSante
    {
        return $this->statutSante;
    }

    public function setStatutSante(StatutSante $statutSante): self
    {
        $this->statutSante = $statutSante;

        return $this;
    }

    public function getCertificatFilename(): ?string
    {
        return $this->certificatFilename;
    }

    public function setCertificatFilename(?string $certificatFilename): self
    {
        $this->certificatFilename = $certificatFilename;

        return $this;
    }

    public function getCertificatFile(): ?File
    {
        return $this->certificatFile;
    }

    public function setCertificatFile(?File $certificatFile): self
    {
        $this->certificatFile = $certificatFile;
        if (null !== $certificatFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isAttestationQsSportValide(): bool
    {
        return $this->attestationQsSportValide;
    }

    public function setAttestationQsSportValide(bool $attestationQsSportValide): self
    {
        $this->attestationQsSportValide = $attestationQsSportValide;

        return $this;
    }

    public function getDateSignatureQsSport(): ?\DateTimeImmutable
    {
        return $this->dateSignatureQsSport;
    }

    public function setDateSignatureQsSport(?\DateTimeImmutable $dateSignatureQsSport): self
    {
        $this->dateSignatureQsSport = $dateSignatureQsSport;

        return $this;
    }

    public function getRemarqueSante(): ?string
    {
        return $this->remarqueSante;
    }

    public function setRemarqueSante(?string $remarqueSante): self
    {
        $this->remarqueSante = $remarqueSante;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasJustificatifSanteComplet(): bool
    {
        return \in_array($this->statutSante, [
            StatutSante::QS_SPORT_VALIDE,
            StatutSante::CERTIFICAT_FOURNI,
            StatutSante::VALIDE_BUREAU,
        ], true);
    }

    public function __toString(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')) ?: 'Danseur';
    }
}
