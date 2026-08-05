<?php

namespace App\Service;

use App\Security\ClubRole;

/**
 * Registre des livrets d'accompagnement générés dynamiquement (HTML/Twig + PDF).
 */
final class LivretRegistry
{
    public const TYPE_PROFESSEURS = 'professeurs';
    public const TYPE_BUREAU = 'bureau';
    public const TYPE_PRESIDENCE = 'presidence';

  /** @var array<string, string> Anciens slugs → types normalisés */
    private const LEGACY_SLUG_ALIASES = [
        'pedagogique' => self::TYPE_PROFESSEURS,
        'strategique' => self::TYPE_PRESIDENCE,
        'bureau' => self::TYPE_BUREAU,
    ];

    /**
     * @return list<array{
     *     type: string,
     *     title: string,
     *     subtitle: string,
     *     pdf_filename: string,
     *     required_role: string,
     *     icon: string,
     *     template_screen: string,
     *     template_pdf: string
     * }>
     */
    public function all(): array
    {
        return [
            [
                'type' => self::TYPE_PROFESSEURS,
                'title' => 'Livret Pédagogique',
                'subtitle' => 'Guide des professeurs de danse — cours, sécurité, bonnes pratiques',
                'pdf_filename' => 'livret-professeurs-studio-danse-430.pdf',
                'required_role' => ClubRole::PROF,
                'icon' => 'fa-book-open',
                'template_screen' => 'admin/documentation/livret_professeurs.html.twig',
                'template_pdf' => 'admin/documentation/pdf/livret_professeurs.html.twig',
            ],
            [
                'type' => self::TYPE_BUREAU,
                'title' => 'Livret Bureau & Gestion',
                'subtitle' => 'Trésorerie, secrétariat, inscriptions, relances et suivi financier',
                'pdf_filename' => 'livret-bureau-studio-danse-430.pdf',
                'required_role' => ClubRole::BUREAU,
                'icon' => 'fa-briefcase',
                'template_screen' => 'admin/documentation/livret_bureau.html.twig',
                'template_pdf' => 'admin/documentation/pdf/livret_bureau.html.twig',
            ],
            [
                'type' => self::TYPE_PRESIDENCE,
                'title' => 'Livret Stratégique & Administration',
                'subtitle' => 'Gouvernance, AG, LDC et pilotage du club',
                'pdf_filename' => 'livret-presidence-studio-danse-430.pdf',
                'required_role' => ClubRole::PRESIDENCE,
                'icon' => 'fa-chess-king',
                'template_screen' => 'admin/documentation/livret_presidence.html.twig',
                'template_pdf' => 'admin/documentation/pdf/livret_presidence.html.twig',
            ],
        ];
    }

    /**
     * @return array{
     *     type: string,
     *     title: string,
     *     subtitle: string,
     *     pdf_filename: string,
     *     required_role: string,
     *     icon: string,
     *     template_screen: string,
     *     template_pdf: string
     * }|null
     */
    public function get(string $typeOrSlug): ?array
    {
        $type = self::LEGACY_SLUG_ALIASES[$typeOrSlug] ?? $typeOrSlug;

        foreach ($this->all() as $livret) {
            if ($livret['type'] === $type) {
                return $livret;
            }
        }

        return null;
    }

    public function normalizeType(string $typeOrSlug): ?string
    {
        $livret = $this->get($typeOrSlug);

        return $livret['type'] ?? null;
    }
}
