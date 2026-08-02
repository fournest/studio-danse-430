<?php

namespace App\Service;

use App\Entity\Danseur;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CoParentInvitationService
{
    private const TTL_SECONDS = 60 * 60 * 24 * 30; // 30 jours

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
    ) {
    }

    public function createRegistrationUrl(Danseur $danseur): string
    {
        $email = mb_strtolower(trim((string) $danseur->getParent2Email()));
        $foyerId = (int) $danseur->getFoyer()?->getId();
        $danseurId = (int) $danseur->getId();
        $expires = time() + self::TTL_SECONDS;
        $token = $this->sign($danseurId, $foyerId, $email, $expires);

        return $this->urlGenerator->generate('app_register', [
            'email' => $email,
            'danseur' => $danseurId,
            'foyer' => $foyerId,
            'expires' => $expires,
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function isValidInvitation(
        int $danseurId,
        int $foyerId,
        string $email,
        int $expires,
        string $token,
    ): bool {
        if ($expires < time()) {
            return false;
        }

        $email = mb_strtolower(trim($email));
        $expected = $this->sign($danseurId, $foyerId, $email, $expires);

        return hash_equals($expected, $token);
    }

    private function sign(int $danseurId, int $foyerId, string $email, int $expires): string
    {
        $payload = sprintf('%d|%d|%s|%d', $danseurId, $foyerId, $email, $expires);

        return hash_hmac('sha256', $payload, $this->appSecret);
    }
}
