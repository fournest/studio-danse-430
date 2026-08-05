<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ActivationPasswordFormType;
use App\Form\ActivationRequestFormType;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Service\AccountActivationTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

final class ActivationController extends AbstractController
{
    #[Route('/activation', name: 'app_activation_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        AccountActivationTokenManager $activationManager,
    ): Response {
        $form = $this->createForm(ActivationRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim((string) $form->get('email')->getData()));
            $user = $userRepository->findOneBy(['email' => $email]);

            if (null === $user) {
                $this->addFlash(
                    'warning',
                    'Aucun compte adhérent trouvé avec cette adresse. Si vous êtes un nouveau membre, utilisez le formulaire d\'inscription classique.'
                );

                return $this->redirectToRoute('app_register');
            }

            if (!$user->needsActivation()) {
                $this->addFlash(
                    'info',
                    'Votre compte est déjà activé. Vous pouvez vous connecter avec votre mot de passe.'
                );

                return $this->redirectToRoute('app_login', ['email' => $email]);
            }

            $activationManager->sendActivationEmail($user);

            $this->addFlash(
                'success',
                'Un e-mail d\'activation a été envoyé. Le lien est valable 48 heures. Consultez votre boîte de réception (et vos spams).'
            );

            return $this->redirectToRoute('app_activation_request');
        }

        return $this->render('security/activation_request.html.twig', [
            'activationForm' => $form,
        ]);
    }

    #[Route('/activation/confirm/{token}', name: 'app_activation_confirm', methods: ['GET', 'POST'])]
    public function confirm(
        string $token,
        Request $request,
        AccountActivationTokenManager $activationManager,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator,
    ): Response {
        $user = $activationManager->validateToken($token);

        if (null === $user) {
            $this->addFlash(
                'danger',
                'Ce lien d\'activation est invalide ou a expiré. Veuillez demander un nouveau lien depuis la page Première connexion.'
            );

            return $this->redirectToRoute('app_activation_request');
        }

        $form = $this->createForm(ActivationPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setIsActivated(true);
            $user->setIsVerified(true);

            $activationManager->consumeToken($token);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte est activé. Bienvenue dans votre Espace Famille !');

            return $userAuthenticator->authenticateUser(
                $user,
                $loginFormAuthenticator,
                $request
            );
        }

        return $this->render('security/activation_reset.html.twig', [
            'activationPasswordForm' => $form,
            'user' => $user,
        ]);
    }
}
