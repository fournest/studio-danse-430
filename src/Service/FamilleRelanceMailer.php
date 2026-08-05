<?php

namespace App\Service;

use App\Entity\Foyer;
use App\Entity\Inscription;
use App\Entity\Paiement;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FamilleRelanceMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $mailFrom = 'noreply@studiodanse430.fr',
    ) {
    }

    public function sendPiecesManquantes(Inscription $inscription): bool
    {
        $foyer = $inscription->getDanseur()?->getFoyer();
        if (null === $foyer) {
            return false;
        }

        $recipients = $this->resolveFamilyEmails($foyer, $inscription);
        if ($recipients === []) {
            return false;
        }

        $espaceFamilleUrl = $this->urlGenerator->generate(
            'app_foyer_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailFrom, 'Studio Danse 430'))
            ->subject('Studio Danse 430 — Pièces manquantes sur votre dossier')
            ->htmlTemplate('emails/relance_pieces_manquantes.html.twig')
            ->context([
                'foyer' => $foyer,
                'inscription' => $inscription,
                'danseur' => $inscription->getDanseur(),
                'cours' => $inscription->getCours(),
                'pieces_manquantes' => $inscription->getPiecesManquantes(),
                'espace_famille_url' => $espaceFamilleUrl,
            ]);

        foreach ($recipients as $recipient) {
            $email->addTo($recipient);
        }

        return $this->dispatch($email);
    }

    public function sendRetardPaiement(Inscription $inscription): bool
    {
        $overdue = $inscription->getOverduePaiements();
        if ($overdue === []) {
            return false;
        }

        $foyer = $inscription->getDanseur()?->getFoyer();
        if (null === $foyer) {
            return false;
        }

        $recipients = $this->resolveFamilyEmails($foyer, $inscription);
        if ($recipients === []) {
            return false;
        }

        $espaceFamilleUrl = $this->urlGenerator->generate(
            'app_foyer_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $lignes = array_map(static function (Paiement $paiement): array {
            return [
                'montant' => $paiement->getMontant(),
                'mode' => $paiement->getMode()->getLabel(),
                'echeance' => $paiement->getDateEncaissementPrevue(),
                'statut' => $paiement->getStatut()->getLabel(),
            ];
        }, $overdue);

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailFrom, 'Studio Danse 430'))
            ->subject('Studio Danse 430 — Règlement en retard')
            ->htmlTemplate('emails/relance_retard_paiement.html.twig')
            ->context([
                'foyer' => $foyer,
                'inscription' => $inscription,
                'danseur' => $inscription->getDanseur(),
                'cours' => $inscription->getCours(),
                'lignes_retard' => $lignes,
                'montant_total_retard' => array_sum(array_column($lignes, 'montant')),
                'espace_famille_url' => $espaceFamilleUrl,
            ]);

        foreach ($recipients as $recipient) {
            $email->addTo($recipient);
        }

        return $this->dispatch($email);
    }

    /**
     * @return list<string>
     */
    private function resolveFamilyEmails(Foyer $foyer, Inscription $inscription): array
    {
        $emails = [];

        $responsable = $foyer->getUser();
        if ($responsable instanceof User && $responsable->getEmail()) {
            $emails[] = mb_strtolower(trim((string) $responsable->getEmail()));
        }

        $payeur = trim((string) $inscription->getPayeurEmail());
        if ($payeur !== '') {
            $emails[] = mb_strtolower($payeur);
        }

        $parent2 = trim((string) $foyer->getParent2Email());
        if ($parent2 !== '') {
            $emails[] = mb_strtolower($parent2);
        }

        return array_values(array_unique(array_filter($emails)));
    }

    private function dispatch(TemplatedEmail $email): bool
    {
        try {
            $this->mailer->send($email);

            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('Échec envoi e-mail relance famille : {message}', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
