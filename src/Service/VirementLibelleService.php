<?php

namespace App\Service;

use App\Entity\Foyer;

/**
 * Génère et mémorise le libellé unique de virement pour un foyer / saison.
 * Exemple : COTIS-2026-DUPONT
 */
final class VirementLibelleService
{
    public function ensureReference(Foyer $foyer, string $saison): string
    {
        $existing = $foyer->getReferenceVirement();
        if (null !== $existing && $existing !== '') {
            return $existing;
        }

        $reference = $this->buildReference($foyer, $saison);
        $foyer->setReferenceVirement($reference);

        return $reference;
    }

    public function buildReference(Foyer $foyer, string $saison): string
    {
        $annee = $this->parseAnneeDebutSaison($saison);
        $slug = $this->slugifyFoyerNom((string) $foyer->getNom());

        return sprintf('COTIS-%d-%s', $annee, $slug);
    }

    private function parseAnneeDebutSaison(string $saison): int
    {
        if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', trim($saison), $m)) {
            return (int) $m[1];
        }

        return (int) date('Y');
    }

    private function slugifyFoyerNom(string $nom): string
    {
        $nom = trim($nom);
        if ($nom === '') {
            return 'FOYER';
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom);
        if (false === $transliterated || $transliterated === '') {
            $transliterated = $nom;
        }

        $slug = preg_replace('/[^A-Za-z0-9]+/', '', strtoupper($transliterated)) ?? '';
        $slug = substr($slug, 0, 24);

        return $slug !== '' ? $slug : 'FOYER';
    }
}
