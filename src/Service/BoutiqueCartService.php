<?php

namespace App\Service;

use App\Entity\Goodie;
use App\Repository\GoodieRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Panier boutique en session (indépendant du tunnel foyer).
 *
 * @phpstan-type CartLine array{goodieId: int, taille: ?string, quantite: int}
 */
final class BoutiqueCartService
{
    private const SESSION_KEY = 'boutique_panier';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly GoodieRepository $goodieRepository,
    ) {
    }

    /**
     * @return list<CartLine>
     */
    public function getRawItems(): array
    {
        $items = $this->session()->get(self::SESSION_KEY, []);

        return \is_array($items) ? array_values($items) : [];
    }

    /**
     * @return list<array{goodie: Goodie, taille: ?string, quantite: int, prixUnitaire: float, prixTotal: float}>
     */
    public function getDetailedItems(): array
    {
        $detailed = [];
        foreach ($this->getRawItems() as $item) {
            $goodie = $this->goodieRepository->find($item['goodieId'] ?? 0);
            if (!$goodie instanceof Goodie || !$goodie->isEstActif()) {
                continue;
            }
            $qty = max(1, (int) ($item['quantite'] ?? 1));
            $prix = $goodie->getPrixUnitaire() ?? 0.0;
            $detailed[] = [
                'goodie' => $goodie,
                'taille' => isset($item['taille']) && $item['taille'] !== '' ? (string) $item['taille'] : null,
                'quantite' => $qty,
                'prixUnitaire' => $prix,
                'prixTotal' => round($prix * $qty, 2),
            ];
        }

        return $detailed;
    }

    public function countItems(): int
    {
        $n = 0;
        foreach ($this->getRawItems() as $item) {
            $n += max(1, (int) ($item['quantite'] ?? 1));
        }

        return $n;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->getDetailedItems() as $item) {
            $total += $item['prixTotal'];
        }

        return round($total, 2);
    }

    public function add(Goodie $goodie, ?string $taille, int $quantite = 1): void
    {
        $quantite = max(1, $quantite);
        $taille = $taille !== null && $taille !== '' ? $taille : null;
        $items = $this->getRawItems();
        $found = false;

        foreach ($items as &$item) {
            if ((int) $item['goodieId'] === $goodie->getId() && ($item['taille'] ?? null) === $taille) {
                $item['quantite'] = max(1, (int) $item['quantite']) + $quantite;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $items[] = [
                'goodieId' => (int) $goodie->getId(),
                'taille' => $taille,
                'quantite' => $quantite,
            ];
        }

        $this->session()->set(self::SESSION_KEY, array_values($items));
    }

    public function updateQuantite(int $goodieId, ?string $taille, int $quantite): void
    {
        $taille = $taille !== null && $taille !== '' ? $taille : null;
        $items = $this->getRawItems();

        foreach ($items as $i => $item) {
            if ((int) $item['goodieId'] === $goodieId && ($item['taille'] ?? null) === $taille) {
                if ($quantite <= 0) {
                    unset($items[$i]);
                } else {
                    $items[$i]['quantite'] = $quantite;
                }
                break;
            }
        }

        $this->session()->set(self::SESSION_KEY, array_values($items));
    }

    public function remove(int $goodieId, ?string $taille): void
    {
        $this->updateQuantite($goodieId, $taille, 0);
    }

    public function clear(): void
    {
        $this->session()->remove(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->getDetailedItems() === [];
    }

    private function session(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
