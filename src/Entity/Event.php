<?php

namespace App\Entity;

use App\Enum\EventType;
use App\Enum\SeatingType;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateHeure;

    #[ORM\ManyToOne(targetEntity: Salle::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private Salle $salle;

    #[ORM\Column]
    private int $placesDisponibles;

    #[ORM\Column(enumType: EventType::class, options: ['default' => 'Gala de Danse'])]
    private EventType $type = EventType::GALA;

    #[ORM\Column(enumType: SeatingType::class, options: ['default' => 'Placement Libre (Jauge simple)'])]
    private SeatingType $modePlacement = SeatingType::LIBRE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $consignesStaff = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'event_benevole')]
    private Collection $benevoles;

    public function __construct()
    {
        $this->benevoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateHeure(): \DateTimeImmutable
    {
        return $this->dateHeure;
    }

    public function setDateHeure(\DateTimeImmutable $dateHeure): static
    {
        $this->dateHeure = $dateHeure;

        return $this;
    }

    public function getSalle(): Salle
    {
        return $this->salle;
    }

    public function setSalle(Salle $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getPlacesDisponibles(): int
    {
        return $this->placesDisponibles;
    }

    public function setPlacesDisponibles(int $placesDisponibles): static
    {
        $this->placesDisponibles = $placesDisponibles;

        return $this;
    }

    public function getType(): EventType
    {
        return $this->type;
    }

    public function setType(EventType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getModePlacement(): SeatingType
    {
        return $this->modePlacement;
    }

    public function setModePlacement(SeatingType $modePlacement): static
    {
        $this->modePlacement = $modePlacement;

        return $this;
    }

    public function getConsignesStaff(): ?string
    {
        return $this->consignesStaff;
    }

    public function setConsignesStaff(?string $consignesStaff): static
    {
        $this->consignesStaff = $consignesStaff !== null && trim($consignesStaff) !== ''
            ? trim($consignesStaff)
            : null;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getBenevoles(): Collection
    {
        return $this->benevoles;
    }

    public function addBenevole(User $benevole): static
    {
        if (!$this->benevoles->contains($benevole)) {
            $this->benevoles->add($benevole);
        }

        return $this;
    }

    public function removeBenevole(User $benevole): static
    {
        $this->benevoles->removeElement($benevole);

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s)',
            $this->nom,
            $this->dateHeure->format('d/m/Y H:i')
        );
    }
}
