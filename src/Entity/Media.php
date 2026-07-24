<?php

namespace App\Entity;

use App\Enum\TypeMedia;
use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[Vich\Uploadable]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: TypeMedia::class)]
    private TypeMedia $type = TypeMedia::IMAGE_LOCAL;

    #[Vich\UploadableField(mapping: 'galerie_images', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $embedUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legende = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Album $album = null;

    public function getId(): ?int { return $this->id; }

    public function getType(): TypeMedia { return $this->type; }
    public function setType(TypeMedia $type): static { $this->type = $type; return $this; }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File { return $this->imageFile; }

    public function getImageName(): ?string { return $this->imageName; }
    public function setImageName(?string $imageName): static { $this->imageName = $imageName; return $this; }

    public function getEmbedUrl(): ?string { return $this->embedUrl; }
    public function setEmbedUrl(?string $embedUrl): static { $this->embedUrl = $embedUrl; return $this; }

    public function getLegende(): ?string { return $this->legende; }
    public function setLegende(?string $legende): static { $this->legende = $legende; return $this; }

    public function getAlbum(): ?Album { return $this->album; }
    public function setAlbum(?Album $album): static { $this->album = $album; return $this; }
}