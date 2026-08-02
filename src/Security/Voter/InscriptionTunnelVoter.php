<?php

namespace App\Security\Voter;

use App\Entity\Inscription;
use App\Entity\User;
use App\Enum\StatutInscription;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Inscription>
 */
final class InscriptionTunnelVoter extends Voter
{
    public const EDIT_TUNNEL = 'INSCRIPTION_EDIT_TUNNEL';
    public const VIEW_CONFIRMATION = 'INSCRIPTION_VIEW_CONFIRMATION';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::EDIT_TUNNEL, self::VIEW_CONFIRMATION], true)
            && $subject instanceof Inscription;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$subject instanceof Inscription) {
            return false;
        }

        $danseur = $subject->getDanseur();
        if (null === $danseur) {
            return false;
        }

        $isOwner = $danseur->getFoyer()?->getUser()?->getId() === $user->getId();
        $isSecondary = false;
        $effectif = $danseur->getParent2EmailEffectif();
        if ($effectif) {
            $isSecondary = mb_strtolower(trim($effectif)) === mb_strtolower(trim((string) $user->getEmail()));
        }

        return match ($attribute) {
            self::EDIT_TUNNEL => $isOwner && $subject->getStatut() === StatutInscription::BROUILLON,
            self::VIEW_CONFIRMATION => $isOwner || $isSecondary,
            default => false,
        };
    }
}
