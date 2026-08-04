<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\InvitationCoparentRepository;
use App\Repository\UserRepository;
use App\Service\CoParentInvitationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CoParentController extends AbstractController
{
    #[Route('/coparent/acceptation/{token}', name: 'app_coparent_acceptation', methods: ['GET'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function acceptation(
        string $token,
        InvitationCoparentRepository $invitationRepository,
        UserRepository $userRepository,
        CoParentInvitationService $invitationService,
    ): Response {
        $invitation = $invitationRepository->findOneBy(['token' => $token]);

        if (null === $invitation) {
            $this->addFlash('danger', 'Lien d’invitation invalide.');

            return $this->redirectToRoute('app_home');
        }

        if ($invitation->isUsed()) {
            $this->addFlash('warning', 'Cette invitation a déjà été utilisée. Connectez-vous pour accéder à votre espace.');

            return $this->redirectToRoute('app_login');
        }

        if ($invitation->isExpired()) {
            $this->addFlash('danger', 'Cette invitation a expiré. Demandez un nouvel envoi au parent titulaire.');

            return $this->redirectToRoute('app_home');
        }

        $email = $invitation->getEmail();
        $existingUser = $userRepository->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // ——— CAS 1 : compte déjà existant ———
        if ($existingUser instanceof User) {
            $current = $this->getUser();

            if ($current instanceof User && mb_strtolower((string) $current->getEmail()) === $email) {
                $result = $invitationService->acceptInvitation($invitation, $current);
                $this->addFlash($result['ok'] ? 'success' : 'danger', $result['message']);

                return $this->redirectToRoute($result['ok'] ? 'app_foyer_index' : 'app_home');
            }

            $invitationService->storeTokenInSession($token);
            $this->addFlash(
                'info',
                'Un compte existe déjà avec cette adresse. Veuillez vous connecter pour valider le rattachement de l’enfant.'
            );

            return $this->redirectToRoute('app_login', [
                'email' => $email,
            ]);
        }

        // ——— CAS 2 : pas de compte ———
        $invitationService->storeTokenInSession($token);
        $this->addFlash(
            'info',
            'Créez votre compte pour rattacher automatiquement l’enfant à votre espace co-parent.'
        );

        return $this->redirectToRoute('app_register', [
            'email' => $email,
            'coparent_token' => $token,
        ]);
    }
}
