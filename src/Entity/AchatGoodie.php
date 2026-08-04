<?php

namespace App\Entity;

use App\Repository\AchatGoodieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AchatGoodieRepository::class)]
class AchatGoodie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'achatsGoodies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Foyer $foyer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Goodie $goodie = null;

    #[ORM\Column(length: 20)]
    private string $saison = '2026/2027';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taille = null;

    #[ORM\Column]
    private int $quantite = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixUnitaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixTotal = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getGoodie(): ?Goodie
    {
        return $this->goodie;
    }

    public function setGoodie(?Goodie $goodie): static
    {
        $this->goodie = $goodie;

        return $this;
    }

    public function getSaison(): string
    {
        return $this->saison;
    }

    public function setSaison(string $saison): static
    {
        $this->saison = $saison;

        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille ?: null;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = max(1, $quantite);

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

    public function getPrixTotal(): ?float
    {
        return null === $this->prixTotal ? null : (float) $this->prixTotal;
    }

    public function setPrixTotal(string|float|int $prixTotal): static
    {
        $this->prixTotal = number_format((float) $prixTotal, 2, '.', '');

        return $this;
    }

    public function recalculerPrixTotal(): static
    {
        $this->setPrixTotal(($this->getPrixUnitaire() ?? 0.0) * $this->quantite);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        $nom = $this->goodie?->getNom() ?? 'Article';
        $taille = $this->taille ? ' · ' . $this->taille : '';

        return sprintf('%s%s × %d', $nom, $taille, $this->quantite);
    }
}
