<?php

namespace App\Entity;

use App\Repository\GoodieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: GoodieRepository::class)]
#[Vich\Uploadable]
class Goodie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixUnitaire = null;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $taillesDisponibles = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $stock = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[Vich\UploadableField(mapping: 'goodies_images', fileNameProperty: 'imageFilename')]
    private ?File $imageFile = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateLivraisonPrevue = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return null === $this->prixUnitaire ? null : (float) $this->prixUnitaire;
    }

    public function setPrixUnitaire(string|float|int $prixUnitaire): static
    {
        $this->prixUnitaire = number_format((float) $prixUnitaire, 2, '.', '');

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getTaillesDisponibles(): array
    {
        return $this->taillesDisponibles ?? [];
    }

    /**
     * @param list<string>|null $taillesDisponibles
     */
    public function setTaillesDisponibles(?array $taillesDisponibles): static
    {
        $this->taillesDisponibles = $taillesDisponibles;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = max(0, $stock);

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): static
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): static
    {
        $this->estActif = $estActif;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDateLivraisonPrevue(): ?\DateTimeImmutable
    {
        return $this->dateLivraisonPrevue;
    }

    public function setDateLivraisonPrevue(?\DateTimeImmutable $dateLivraisonPrevue): static
    {
        $this->dateLivraisonPrevue = $dateLivraisonPrevue;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Vente éphémère ouverte : pas de borne, ou bornes respectées par rapport à maintenant.
     */
    public function isAvailableForSale(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        if (null !== $this->dateDebut && $this->dateDebut > $now) {
            return false;
        }

        if (null !== $this->dateFin && $this->dateFin < $now) {
            return false;
        }

        return true;
    }

    /**
     * Statut affiché en admin : En cours / À venir / Terminée.
     */
    public function getSaleStatus(?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();

        if (null !== $this->dateDebut && $this->dateDebut > $now) {
            return 'À venir';
        }

        if (null !== $this->dateFin && $this->dateFin < $now) {
            return 'Terminée';
        }

        return 'En cours';
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Goodie';
    }
}
