<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\CoParentInvitationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
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
    ): Response {
        $user = new User();

        $inviteEmail = trim((string) $request->query->get('email', ''));
        $danseurId = (int) $request->query->get('danseur', 0);
        $foyerId = (int) $request->query->get('foyer', 0);
        $expires = (int) $request->query->get('expires', 0);
        $token = (string) $request->query->get('token', '');
        $isCoParentInvite = false;

        if (
            $inviteEmail !== ''
            && $danseurId > 0
            && $foyerId > 0
            && $expires > 0
            && $token !== ''
            && $coParentInvitation->isValidInvitation($danseurId, $foyerId, $inviteEmail, $expires, $token)
        ) {
            $user->setEmail($inviteEmail);
            $isCoParentInvite = true;
        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

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
                    ? 'Votre compte co-parent a été créé. Vérifiez vos e-mails pour l’activer, puis connectez-vous pour consulter la fiche de votre enfant.'
                    : 'Votre compte a été créé. Veuillez vérifier vos e-mails pour activer votre compte avant de vous connecter.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'is_coparent_invite' => $isCoParentInvite,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
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

        $this->addFlash('success', 'Votre adresse e-mail est confirmée. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}
