<?php

namespace App\Entity;

use App\Repository\SponsorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Le nom reste obligatoire (pas de nullable: true)
    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    // LE LIEN DEVIENT OPTIONNEL
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lien = null;

    // LE LOGO DEVIENT OPTIONNEL
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

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

    public function getLien(): ?string
    {
        return $this->lien;
    }

    // Correction ici : l'argument accepte désormais null (?string)
    public function setLien(?string $lien): static
    {
        $this->lien = $lien;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }
}