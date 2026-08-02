<?php

namespace App\Service;

use App\Entity\Foyer;
use App\Entity\Inscription;
use App\Entity\User;
use App\Enum\ModePaiement;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class InscriptionConfirmationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $helloAssoCampaignUrl,
        private readonly string $mailFrom = 'noreply@studiodanse430.fr',
    ) {
    }

    /**
     * @param list<Inscription> $inscriptions
     */
    public function sendConfirmation(User $responsable, Foyer $foyer, array $inscriptions): void
    {
        $emailDest = $responsable->getEmail();
        if (!$emailDest) {
            return;
        }

        $hasHelloAsso = false;
        $hasVirement = false;
        foreach ($inscriptions as $inscription) {
            if ($inscription->utiliseHelloAsso()) {
                $hasHelloAsso = true;
            }
            if ($inscription->utiliseVirement()) {
                $hasVirement = true;
            }
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailFrom, 'Studio Danse 430'))
            ->to($emailDest)
            ->subject('Studio Danse 430 - Confirmation de votre demande d\'inscription')
            ->htmlTemplate('emails/inscription_confirmation.html.twig')
            ->context([
                'foyer' => $foyer,
                'responsable' => $responsable,
                'inscriptions' => $inscriptions,
                'hasHelloAsso' => $hasHelloAsso,
                'hasVirement' => $hasVirement,
                'helloAssoUrl' => $this->helloAssoCampaignUrl,
                'modes' => ModePaiement::cases(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('Échec envoi e-mail confirmation inscription : {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
