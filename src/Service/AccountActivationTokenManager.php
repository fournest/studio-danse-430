<?php

namespace App\Service;

use App\Entity\AccountActivationToken;
use App\Entity\User;
use App\Repository\AccountActivationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Génération, envoi et validation des jetons d'activation (48 h).
 */
final class AccountActivationTokenManager
{
    private const EXPIRY_HOURS = 48;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountActivationTokenRepository $tokenRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function sendActivationEmail(User $user): void
    {
        $plainToken = $this->createToken($user);
        $activationUrl = $this->urlGenerator->generate(
            'app_activation_confirm',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address('studiodanse430@gmail.com', 'Studio Danse 430'))
            ->to((string) $user->getEmail())
            ->subject('Activez votre compte Studio Danse 430')
            ->htmlTemplate('emails/activation_account.html.twig')
            ->context([
                'user' => $user,
                'activationUrl' => $activationUrl,
                'expiresHours' => self::EXPIRY_HOURS,
            ]);

        $this->mailer->send($email);
    }

    public function createToken(User $user): string
    {
        $this->tokenRepository->deleteForUser($user);

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $activationToken = new AccountActivationToken(
            $user,
            new \DateTimeImmutable(sprintf('+%d hours', self::EXPIRY_HOURS)),
            $tokenHash
        );

        $this->entityManager->persist($activationToken);
        $this->entityManager->flush();

        return $plainToken;
    }

    public function validateToken(string $plainToken): ?User
    {
        if (\strlen($plainToken) < 32) {
            return null;
        }

        $tokenHash = hash('sha256', $plainToken);
        $stored = $this->tokenRepository->findValidByHash($tokenHash);

        if (null === $stored || $stored->isExpired()) {
            return null;
        }

        return $stored->getUser();
    }

    public function consumeToken(string $plainToken): void
    {
        $tokenHash = hash('sha256', $plainToken);
        $stored = $this->tokenRepository->findValidByHash($tokenHash);
        if (null !== $stored) {
            $this->entityManager->remove($stored);
            $this->entityManager->flush();
        }
    }
}
