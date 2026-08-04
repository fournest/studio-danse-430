<?php

namespace App\Entity;

use App\Repository\CommandeBoutiqueLigneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeBoutiqueLigneRepository::class)]
class CommandeBoutiqueLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CommandeBoutique $commande = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Goodie $goodie = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taille = null;

    #[ORM\Column]
    private int $quantite = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $prixUnitaire = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $prixTotal = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?CommandeBoutique
    {
        return $this->commande;
    }

    public function setCommande(?CommandeBoutique $commande): static
    {
        $this->commande = $commande;

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

    public function getPrixUnitaire(): float
    {
        return (float) $this->prixUnitaire;
    }

    public function setPrixUnitaire(string|float|int $prixUnitaire): static
    {
        $this->prixUnitaire = number_format((float) $prixUnitaire, 2, '.', '');

        return $this;
    }

    public function getPrixTotal(): float
    {
        return (float) $this->prixTotal;
    }

    public function setPrixTotal(string|float|int $prixTotal): static
    {
        $this->prixTotal = number_format((float) $prixTotal, 2, '.', '');

        return $this;
    }

    public function recalculerPrixTotal(): static
    {
        return $this->setPrixTotal($this->getPrixUnitaire() * $this->quantite);
    }
}
