<?php

namespace App\Entity;

use App\Enum\ModePaiementBoutique;
use App\Enum\ModeRetraitBoutique;
use App\Enum\StatutCommandeBoutique;
use App\Repository\CommandeBoutiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeBoutiqueRepository::class)]
class CommandeBoutique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Foyer $foyer = null;

    #[ORM\Column(length: 120)]
    private string $email = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 120)]
    private string $nomComplet = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: Types::STRING, enumType: ModeRetraitBoutique::class)]
    private ModeRetraitBoutique $modeRetrait = ModeRetraitBoutique::RETRAIT_CLUB;

    #[ORM\Column(type: Types::STRING, enumType: ModePaiementBoutique::class)]
    private ModePaiementBoutique $modePaiement = ModePaiementBoutique::CHEQUE;

    #[ORM\Column(type: Types::STRING, enumType: StatutCommandeBoutique::class)]
    private StatutCommandeBoutique $statut = StatutCommandeBoutique::EN_ATTENTE;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, CommandeBoutiqueLigne> */
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeBoutiqueLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone ? User::formatTelephone($telephone) : null;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): static
    {
        $this->nomComplet = trim($nomComplet);

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse ? trim($adresse) : null;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal ? trim($codePostal) : null;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville ? trim($ville) : null;

        return $this;
    }

    public function getModeRetrait(): ModeRetraitBoutique
    {
        return $this->modeRetrait;
    }

    public function setModeRetrait(ModeRetraitBoutique $modeRetrait): static
    {
        $this->modeRetrait = $modeRetrait;

        return $this;
    }

    public function getModePaiement(): ModePaiementBoutique
    {
        return $this->modePaiement;
    }

    public function setModePaiement(ModePaiementBoutique $modePaiement): static
    {
        $this->modePaiement = $modePaiement;

        return $this;
    }

    public function getStatut(): StatutCommandeBoutique
    {
        return $this->statut;
    }

    public function setStatut(StatutCommandeBoutique $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getTotal(): float
    {
        return (float) $this->total;
    }

    public function setTotal(string|float|int $total): static
    {
        $this->total = number_format((float) $total, 2, '.', '');

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, CommandeBoutiqueLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(CommandeBoutiqueLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }

        return $this;
    }

    public function removeLigne(CommandeBoutiqueLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne) && $ligne->getCommande() === $this) {
            $ligne->setCommande(null);
        }

        return $this;
    }

    public function recalculerTotal(): static
    {
        $sum = 0.0;
        foreach ($this->lignes as $ligne) {
            $sum += $ligne->getPrixTotal() ?? 0.0;
        }

        return $this->setTotal($sum);
    }

    public function __toString(): string
    {
        return sprintf('Commande #%s', $this->id ?? '?');
    }
}
