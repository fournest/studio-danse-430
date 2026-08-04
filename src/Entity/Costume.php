<?php

namespace App\Entity;

use App\Repository\CostumeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CostumeRepository::class)]
class Costume
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taille = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(nullable: true)]
    private ?int $prix = null;

    #[ORM\Column]
    private ?int $quantite = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $disponibleHorsGala = true;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tarifLocationHorsGala = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $theme = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $genre = null;

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

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    /**
     * Tailles proposées sous forme de choix Symfony (valeur => libellé).
     * Accepte une valeur unique, une liste (« S, M, L »), un intervalle (« S à L ») ou du JSON.
     *
     * @return array<string, string>
     */
    public function getTaillesAsArray(): array
    {
        $raw = trim((string) $this->taille);
        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (\is_array($decoded)) {
                return $this->normalizeTailleChoices(array_map(static fn ($t) => (string) $t, $decoded));
            }
        }

        if (preg_match('/[,;\/|]/', $raw)) {
            $parts = preg_split('/\s*[,;\/|]\s*/u', $raw) ?: [];

            return $this->normalizeTailleChoices($parts);
        }

        if (preg_match('/^(.+?)\s*(?:à|a|-|–|—)\s*(.+)$/ui', $raw, $m)) {
            $expanded = $this->expandTailleRange(trim($m[1]), trim($m[2]));
            if ($expanded !== []) {
                return $this->normalizeTailleChoices($expanded);
            }
        }

        return $this->normalizeTailleChoices([$raw]);
    }

    /**
     * @return list<string>
     */
    public function getTaillesDisponibles(): array
    {
        return array_values($this->getTaillesAsArray());
    }

    /**
     * @param list<string> $values
     *
     * @return array<string, string>
     */
    private function normalizeTailleChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            $choices[$value] = $value;
        }

        return $choices;
    }

    /**
     * @return list<string>
     */
    private function expandTailleRange(string $start, string $end): array
    {
        $order = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];
        $startKey = strtoupper($start);
        $endKey = strtoupper($end);
        $iStart = array_search($startKey, $order, true);
        $iEnd = array_search($endKey, $order, true);

        if (false === $iStart || false === $iEnd) {
            return [];
        }

        if ($iStart > $iEnd) {
            [$iStart, $iEnd] = [$iEnd, $iStart];
        }

        return \array_slice($order, (int) $iStart, (int) $iEnd - (int) $iStart + 1);
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

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(?int $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function isDisponibleHorsGala(): bool
    {
        return $this->disponibleHorsGala;
    }

    public function setDisponibleHorsGala(bool $disponibleHorsGala): static
    {
        $this->disponibleHorsGala = $disponibleHorsGala;

        return $this;
    }

    public function getTarifLocationHorsGala(): ?float
    {
        return null === $this->tarifLocationHorsGala ? null : (float) $this->tarifLocationHorsGala;
    }

    public function setTarifLocationHorsGala(string|float|int|null $tarifLocationHorsGala): static
    {
        if (null === $tarifLocationHorsGala || '' === $tarifLocationHorsGala) {
            $this->tarifLocationHorsGala = null;
        } else {
            $this->tarifLocationHorsGala = number_format((float) $tarifLocationHorsGala, 2, '.', '');
        }

        return $this;
    }

    /**
     * Tarif affiché / facturé pour une location hors gala (soirée / week-end).
     */
    public function getTarifLocationEffectif(): ?float
    {
        if (null !== $this->getTarifLocationHorsGala()) {
            return $this->getTarifLocationHorsGala();
        }

        return null === $this->prix ? null : (float) $this->prix;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Costume #' . $this->id;
    }
}
