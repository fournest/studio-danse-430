<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    /**
     * Vérifications AVANT que le mot de passe ne soit vérifié.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Si le compte est désactivé par l'association
        if (!$user->isActif()) {
            throw new DisabledException("Votre compte a été suspendu par l'association. Veuillez contacter le bureau.");
        }
    }

    /**
     * Vérifications APRÈS que le mot de passe a été validé.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Si l'utilisateur n'a pas encore validé son adresse email
        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException("Veuillez valider votre adresse email avant de pouvoir vous connecter.");
        }
    }
}