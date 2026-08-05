<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\InvitationCoparentRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\CoParentInvitationService;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        CoParentInvitationService $coParentInvitation,
        InvitationCoparentRepository $invitationRepository,
    ): Response {
        $user = new User();

        $inviteEmail = trim((string) $request->query->get('email', ''));
        $coparentToken = trim((string) $request->query->get('coparent_token', ''));
        $isCoParentInvite = false;

        if ($coparentToken === '') {
            $coparentToken = (string) ($coParentInvitation->peekTokenFromSession() ?? '');
        }

        if ($coparentToken !== '') {
            $invitation = $invitationRepository->findOneValidByToken($coparentToken);
            if (null !== $invitation) {
                $inviteEmail = $invitation->getEmail();
                $user->setEmail($inviteEmail);
                $isCoParentInvite = true;
                $coParentInvitation->storeTokenInSession($coparentToken);
            }
        } elseif ($inviteEmail !== '') {
            // Pré-remplissage simple (lien manuel ou ancien format)
            $user->setEmail(mb_strtolower($inviteEmail));
            $isCoParentInvite = true;
        }

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'lock_email' => $isCoParentInvite && $inviteEmail !== '',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Email verrouillé : réimpose l’email d’invitation (champ disabled non soumis).
            if ($isCoParentInvite && $inviteEmail !== '') {
                $user->setEmail(mb_strtolower($inviteEmail));
            }

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setIsActivated(true);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // Conserve le token pour finaliser le rattachement après connexion.
            if ($coparentToken !== '') {
                $coParentInvitation->storeTokenInSession($coparentToken);
            }

            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('studiodanse430@gmail.com', 'Studio Danse 430'))
                    ->to((string) $user->getEmail())
                    ->subject('Confirmez votre adresse e-mail')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash(
                'success',
                $isCoParentInvite
                    ? 'Votre compte co-parent a été créé. Vérifiez vos e-mails pour l’activer, puis connectez-vous : le rattachement de l’enfant sera finalisé automatiquement.'
                    : 'Votre compte a été créé. Veuillez vérifier vos e-mails pour activer votre compte avant de vous connecter.'
            );

            return $this->redirectToRoute('app_login', [
                'email' => $user->getEmail(),
            ]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'is_coparent_invite' => $isCoParentInvite,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
        UserRepository $userRepository,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator,
    ): Response {
        $id = $request->query->get('id');
        if (null === $id) {
            $this->addFlash('verify_email_error', 'Lien de confirmation invalide.');

            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id);
        if (null === $user) {
            $this->addFlash('verify_email_error', 'Impossible de trouver un compte associé à ce lien.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Votre adresse e-mail est confirmée. Créez maintenant votre foyer pour inscrire vos danseurs aux cours.');

        return $userAuthenticator->authenticateUser(
            $user,
            $loginFormAuthenticator,
            $request
        );
    }
}
