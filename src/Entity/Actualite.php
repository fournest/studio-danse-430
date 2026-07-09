<?php

namespace App\Entity;

use App\Repository\ActualiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActualiteRepository::class)]
class Actualite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $urlMedia = null; // Lien vers l'image de la publication

    #[ORM\Column(length: 500)]
    private ?string $urlOrigine = null; // Lien cliquable direct vers le post FB/Insta

    #[ORM\Column(length: 50)]
    private ?string $plateforme = null; // 'facebook' ou 'instagram'

    #[ORM\Column]
    private ?\DateTimeImmutable $datePublication = null;

    public function getId(): ?int { return $this->id; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): self { $this->contenu = $contenu; return $this; }

    public function getUrlMedia(): ?string { return $this->urlMedia; }
    public function setUrlMedia(?string $urlMedia): self { $this->urlMedia = $urlMedia; return $this; }

    public function getUrlOrigine(): ?string { return $this->urlOrigine; }
    public function setUrlOrigine(string $urlOrigine): self { $this->urlOrigine = $urlOrigine; return $this; }

    public function getPlateforme(): ?string { return $this->plateforme; }
    public function setPlateforme(string $plateforme): self { $this->plateforme = $plateforme; return $this; }

    public function getDatePublication(): ?\DateTimeImmutable { return $this->datePublication; }
    public function setDatePublication(\DateTimeImmutable $datePublication): self { $this->datePublication = $datePublication; return $this; }
}