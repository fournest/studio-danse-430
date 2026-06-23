<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Dernière erreur d'authentification (mauvais identifiants, etc.).
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier identifiant saisi par l'utilisateur.
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode reste volontairement vide : elle est interceptée
        // par la clé "logout" du firewall (cf. config/packages/security.yaml).
        throw new \LogicException('Cette méthode est interceptée par le firewall de déconnexion.');
    }
}
