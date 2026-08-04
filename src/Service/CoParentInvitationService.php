<?php

namespace App\Service;

use App\Entity\Danseur;
use App\Entity\InvitationCoparent;
use App\Entity\User;
use App\Repository\InvitationCoparentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CoParentInvitationService
{
    public const SESSION_TOKEN_KEY = 'coparent_invitation_token';
    private const TTL_SECONDS = 60 * 60 * 24 * 30; // 30 jours

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $em,
        private readonly InvitationCoparentRepository $invitationRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Crée (ou renouvelle) une invitation persistée et retourne l'URL d'acceptation.
     */
    public function createInvitation(Danseur $danseur): InvitationCoparent
    {
        $email = mb_strtolower(trim((string) $danseur->getParent2Email()));
        if ($email === '' || null === $danseur->getId()) {
            throw new \InvalidArgumentException('Impossible de créer une invitation sans email co-parent ni danseur persisté.');
        }

        // Invalide les invitations pendantes précédentes pour ce couple danseur/email.
        $pendings = $this->invitationRepository->createQueryBuilder('i')
            ->andWhere('i.danseur = :danseur')
            ->andWhere('i.email = :email')
            ->andWhere('i.usedAt IS NULL')
            ->setParameter('danseur', $danseur)
            ->setParameter('email', $email)
            ->getQuery()
            ->getResult();

        foreach ($pendings as $pending) {
            $pending->setExpiresAt(new \DateTimeImmutable('-1 second'));
        }

        $invitation = new InvitationCoparent();
        $invitation->setDanseur($danseur);
        $invitation->setEmail($email);
        $invitation->setExpiresAt(new \DateTimeImmutable('+' . self::TTL_SECONDS . ' seconds'));

        $this->em->persist($invitation);

        return $invitation;
    }

    public function createAcceptationUrl(InvitationCoparent $invitation): string
    {
        return $this->urlGenerator->generate('app_coparent_acceptation', [
            'token' => $invitation->getToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function storeTokenInSession(string $token): void
    {
        $this->requestStack->getSession()->set(self::SESSION_TOKEN_KEY, $token);
    }

    public function consumeTokenFromSession(): ?string
    {
        $session = $this->requestStack->getSession();
        $token = $session->get(self::SESSION_TOKEN_KEY);
        $session->remove(self::SESSION_TOKEN_KEY);

        return \is_string($token) && $token !== '' ? $token : null;
    }

    public function peekTokenFromSession(): ?string
    {
        $token = $this->requestStack->getSession()->get(self::SESSION_TOKEN_KEY);

        return \is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Finalise le rattachement : le danseur reste sur le foyer principal,
     * le co-parent y accède via son email (parent2Email) une fois l'invitation acceptée.
     *
     * @return array{ok: bool, message: string, danseur: ?Danseur}
     */
    public function acceptInvitation(InvitationCoparent $invitation, User $user): array
    {
        if (!$invitation->isValid()) {
            return [
                'ok' => false,
                'message' => $invitation->isUsed()
                    ? 'Cette invitation a déjà été utilisée.'
                    : 'Cette invitation a expiré. Demandez un nouvel envoi au parent titulaire.',
                'danseur' => $invitation->getDanseur(),
            ];
        }

        $userEmail = mb_strtolower(trim((string) $user->getEmail()));
        if ($userEmail !== $invitation->getEmail()) {
            return [
                'ok' => false,
                'message' => sprintf(
                    'Connectez-vous avec l’adresse %s pour accepter cette invitation.',
                    $invitation->getEmail()
                ),
                'danseur' => $invitation->getDanseur(),
            ];
        }

        $danseur = $invitation->getDanseur();
        if (null === $danseur) {
            return [
                'ok' => false,
                'message' => 'Le danseur lié à cette invitation est introuvable.',
                'danseur' => null,
            ];
        }

        // Garantit le matching email pour l’accès co-parent (lecture seule).
        $danseur->setParent2Email($userEmail);
        if (null === $danseur->getParent2InvitedAt()) {
            $danseur->setParent2InvitedAt(new \DateTimeImmutable());
        }

        $invitation->markUsed($user);
        $this->em->flush();

        $prenom = $danseur->getPrenom() ?: 'l’enfant';

        return [
            'ok' => true,
            'message' => sprintf('L’enfant %s a été rattaché à votre compte avec succès !', $prenom),
            'danseur' => $danseur,
        ];
    }

    /**
     * @return array{ok: bool, message: string, danseur: ?Danseur}|null null si aucun token en session
     */
    public function acceptPendingFromSession(User $user): ?array
    {
        $token = $this->consumeTokenFromSession();
        if (null === $token) {
            return null;
        }

        $invitation = $this->invitationRepository->findOneBy(['token' => $token]);
        if (null === $invitation) {
            return [
                'ok' => false,
                'message' => 'Invitation introuvable ou invalide.',
                'danseur' => null,
            ];
        }

        return $this->acceptInvitation($invitation, $user);
    }

    /** @deprecated Conservé pour d’éventuels anciens liens HMAC — préférer createInvitation(). */
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

        // Ancien format : si une invitation DB existe encore pour ce danseur/email, OK.
        $pending = $this->invitationRepository->findLatestPendingForDanseurEmail($danseurId, $email);

        return null !== $pending;
    }
}
