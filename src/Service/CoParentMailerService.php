<?php

namespace App\Service;

use App\Entity\Danseur;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class CoParentMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly CoParentInvitationService $invitationService,
    ) {
    }

    public function sendInvitation(Danseur $danseur): bool
    {
        $emailTo = trim((string) $danseur->getParent2Email());
        if ($emailTo === '' || null === $danseur->getId() || null === $danseur->getFoyer()?->getId()) {
            return false;
        }

        $registerUrl = $this->invitationService->createRegistrationUrl($danseur);
        $prenom = $danseur->getParent2Prenom() ?: 'Parent';

        $email = (new TemplatedEmail())
            ->from(new Address('studiodanse430@gmail.com', 'Studio Danse 430'))
            ->to($emailTo)
            ->subject(sprintf('Studio Danse 430 — Suivi des cours de %s', $danseur->getPrenom() ?? 'votre enfant'))
            ->htmlTemplate('emails/parent2_invitation.html.twig')
            ->context([
                'danseur' => $danseur,
                'parent2_prenom' => $prenom,
                'register_url' => $registerUrl,
                'saison' => '2026-2027',
            ]);

        $this->mailer->send($email);
        $danseur->setParent2InvitedAt(new \DateTimeImmutable());

        return true;
    }
}
