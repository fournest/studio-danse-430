<?php

namespace App\Entity;

use App\Repository\PageLegaleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageLegaleRepository::class)]
#[ORM\Table(name: 'page_legale')]
#[ORM\UniqueConstraint(name: 'UNIQ_PAGE_LEGALE_SLUG', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class PageLegale
{
    public const SLUG_MENTIONS_LEGALES = 'mentions-legales';
    public const SLUG_POLITIQUE_CONFIDENTIALITE = 'politique-de-confidentialite';
    public const SLUG_CGU = 'cgu';

    /** @var list<string> */
    public const SLUGS_REQUIS = [
        self::SLUG_MENTIONS_LEGALES,
        self::SLUG_POLITIQUE_CONFIDENTIALITE,
        self::SLUG_CGU,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isSlugRequis(): bool
    {
        return \in_array($this->slug, self::SLUGS_REQUIS, true);
    }

    public function __toString(): string
    {
        return $this->titre ?? 'Page légale';
    }
}
