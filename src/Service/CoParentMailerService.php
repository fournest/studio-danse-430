<?php

namespace App\Service;

use App\Entity\Danseur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class CoParentMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly CoParentInvitationService $invitationService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function sendInvitation(Danseur $danseur): bool
    {
        $emailTo = trim((string) $danseur->getParent2Email());
        if ($emailTo === '' || null === $danseur->getId() || null === $danseur->getFoyer()?->getId()) {
            return false;
        }

        $invitation = $this->invitationService->createInvitation($danseur);
        $acceptUrl = $this->invitationService->createAcceptationUrl($invitation);
        $prenom = $danseur->getParent2Prenom() ?: 'Parent';

        $email = (new TemplatedEmail())
            ->from(new Address('studiodanse430@gmail.com', 'Studio Danse 430'))
            ->to($emailTo)
            ->subject(sprintf('Studio Danse 430 — Suivi des cours de %s', $danseur->getPrenom() ?? 'votre enfant'))
            ->htmlTemplate('emails/parent2_invitation.html.twig')
            ->context([
                'danseur' => $danseur,
                'parent2_prenom' => $prenom,
                'register_url' => $acceptUrl,
                'accept_url' => $acceptUrl,
                'saison' => '2026-2027',
            ]);

        $this->mailer->send($email);
        $danseur->setParent2InvitedAt(new \DateTimeImmutable());
        $this->em->flush();

        return true;
    }
}
