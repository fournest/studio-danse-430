<?php

namespace App\Security;

/**
 * Rôles officiels du club Studio Danse 430.
 * Seuls les rôles « officiels » sont stockés en base ; ROLE_BUREAU et ROLE_PRESIDENCE
 * sont des rôles implicites fournis par la hiérarchie Symfony (security.yaml).
 */
final class ClubRole
{
    public const USER = 'ROLE_USER';
    public const PROF = 'ROLE_PROF';
    public const SCANNER = 'ROLE_SCANNER';
    public const TRESORIER = 'ROLE_TRESORIER';
    public const TRESORIER_ADJOINT = 'ROLE_TRESORIER_ADJOINT';
    public const SECRETAIRE = 'ROLE_SECRETAIRE';
    public const PRESIDENT = 'ROLE_PRESIDENT';
    public const VICE_PRESIDENT = 'ROLE_VICE_PRESIDENT';

    /** Rôle legacy conservé pour compatibilité avec les comptes existants. */
    public const BUREAU_LEGACY = 'ROLE_BUREAU';

    public const PRESIDENCE = 'ROLE_PRESIDENCE';
    public const BUREAU = 'ROLE_BUREAU';
    public const ADMIN = 'ROLE_ADMIN';

    /** @var list<string> */
    public const OFFICIAL_STORED = [
        self::PROF,
        self::SCANNER,
        self::TRESORIER,
        self::TRESORIER_ADJOINT,
        self::SECRETAIRE,
        self::PRESIDENT,
        self::VICE_PRESIDENT,
        self::BUREAU_LEGACY,
    ];

    /** @var list<string> */
    public const BUREAU_MEMBERS = [
        self::TRESORIER,
        self::TRESORIER_ADJOINT,
        self::SECRETAIRE,
        self::PRESIDENT,
        self::VICE_PRESIDENT,
        self::BUREAU_LEGACY,
    ];

    /** @var list<string> */
    public const PRESIDENCE_MEMBERS = [
        self::PRESIDENT,
        self::VICE_PRESIDENT,
    ];

    /**
     * @return array<string, string> Libellé français => rôle Symfony
     */
    public static function assignableChoices(bool $includePresidence = true): array
    {
        $choices = [
            'Adhérent / Parent' => self::USER,
            'Professeur de danse' => self::PROF,
            'Scanner entrée (bénévole)' => self::SCANNER,
            'Trésorier(ière)' => self::TRESORIER,
            'Trésorier(ière) adjoint(e)' => self::TRESORIER_ADJOINT,
            'Secrétaire' => self::SECRETAIRE,
        ];

        if ($includePresidence) {
            $choices['Président(e)'] = self::PRESIDENT;
            $choices['Vice-Président(e)'] = self::VICE_PRESIDENT;
        }

        return $choices;
    }

    public static function label(string $role): string
    {
        foreach (self::assignableChoices(true) as $label => $value) {
            if ($value === $role) {
                return $label;
            }
        }

        return match ($role) {
            self::BUREAU_LEGACY => 'Membre du bureau (legacy — à préciser)',
            self::USER => 'Adhérent / Parent',
            self::SCANNER => 'Scanner entrée (bénévole)',
            default => $role,
        };
    }

    public static function isBureauMember(string $role): bool
    {
        return \in_array($role, self::BUREAU_MEMBERS, true);
    }

    public static function isPresidenceMember(string $role): bool
    {
        return \in_array($role, self::PRESIDENCE_MEMBERS, true);
    }

    /**
     * @param list<string> $roles
     */
    public static function extractPrimaryRole(array $roles): string
    {
        foreach (self::OFFICIAL_STORED as $officialRole) {
            if (\in_array($officialRole, $roles, true)) {
                return $officialRole;
            }
        }

        return self::USER;
    }

    /**
     * Alias CLI / raccourcis pour app:promote-user (ex. president, tresorier, prof…).
     *
     * @return array<string, string>
     */
    public static function cliAliases(): array
    {
        return [
            'adherent' => self::USER,
            'parent' => self::USER,
            'user' => self::USER,
            'prof' => self::PROF,
            'professeur' => self::PROF,
            'scanner' => self::SCANNER,
            'benevole' => self::SCANNER,
            'tresorier' => self::TRESORIER,
            'tresorier-adjoint' => self::TRESORIER_ADJOINT,
            'tresorier_adjoint' => self::TRESORIER_ADJOINT,
            'secretaire' => self::SECRETAIRE,
            'president' => self::PRESIDENT,
            'vice-president' => self::VICE_PRESIDENT,
            'vice_president' => self::VICE_PRESIDENT,
            'bureau' => self::BUREAU_LEGACY,
        ];
    }

    /**
     * Résout un alias CLI ou un identifiant ROLE_* vers le rôle stocké en base.
     */
    public static function resolveAlias(string $input): ?string
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return null;
        }

        $upper = strtoupper(str_replace('-', '_', $trimmed));
        if (str_starts_with($upper, 'ROLE_') && \in_array($upper, self::OFFICIAL_STORED, true)) {
            return $upper;
        }
        if ($upper === 'ROLE_USER') {
            return self::USER;
        }

        $key = strtolower(str_replace('_', '-', $trimmed));

        return self::cliAliases()[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function cliAliasesHelpLines(): array
    {
        $lines = [];
        foreach (self::cliAliases() as $alias => $role) {
            $lines[] = sprintf('  %-22s → %s (%s)', $alias, $role, self::label($role));
        }

        return $lines;
    }
}
