<?php

namespace App\Service;

use App\Entity\DemandeFusionFoyer;
use App\Entity\Foyer;
use App\Entity\User;
use App\Repository\DemandeFusionFoyerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FoyerFusionService
{
    private const TTL_SECONDS = 60 * 60 * 24 * 14; // 14 jours

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DemandeFusionFoyerRepository $demandeRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createAndSendDemande(Foyer $foyerSource, Foyer $foyerTarget, User $demandeur): DemandeFusionFoyer
    {
        if ($foyerSource->getId() === $foyerTarget->getId()) {
            throw new \InvalidArgumentException('Impossible de fusionner un foyer avec lui-même.');
        }

        $destinataire = $foyerTarget->getUser();
        if (null === $destinataire || !$destinataire->getEmail()) {
            throw new \RuntimeException('Le foyer cible n’a pas de titulaire joignable par e-mail.');
        }

        foreach ($this->demandeRepository->findPendingBetween($foyerSource, $foyerTarget) as $pending) {
            $pending->setExpiresAt(new \DateTimeImmutable('-1 second'));
        }

        $demande = new DemandeFusionFoyer();
        $demande->setFoyerSource($foyerSource);
        $demande->setFoyerTarget($foyerTarget);
        $demande->setDemandeur($demandeur);
        $demande->setExpiresAt(new \DateTimeImmutable('+' . self::TTL_SECONDS . ' seconds'));

        $this->em->persist($demande);
        $this->em->flush();

        $acceptUrl = $this->urlGenerator->generate(
            'app_foyer_valider_fusion',
            ['token' => $demande->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $nomDemandeur = trim((string) $foyerSource->getNom()) ?: (string) $demandeur->getEmail();

        $email = (new TemplatedEmail())
            ->from(new Address('studiodanse430@gmail.com', 'Studio Danse 430'))
            ->to((string) $destinataire->getEmail())
            ->subject('Demande de raccordement de foyer - Studio Danse 430')
            ->htmlTemplate('emails/demande_fusion_foyer.html.twig')
            ->context([
                'nom_demandeur' => $nomDemandeur,
                'foyer_source' => $foyerSource,
                'foyer_target' => $foyerTarget,
                'accept_url' => $acceptUrl,
            ]);

        $this->mailer->send($email);

        return $demande;
    }

    /**
     * Fusionne le foyer source dans le foyer cible, puis supprime le doublon vide.
     *
     * @return array{ok: bool, message: string}
     */
    public function accepterFusion(DemandeFusionFoyer $demande, User $accepteur): array
    {
        if (!$demande->isValid()) {
            return [
                'ok' => false,
                'message' => $demande->isUsed()
                    ? 'Cette demande de fusion a déjà été traitée.'
                    : 'Cette demande de fusion a expiré.',
            ];
        }

        $foyerTarget = $demande->getFoyerTarget();
        $foyerSource = $demande->getFoyerSource();

        if (null === $foyerTarget || null === $foyerSource) {
            return ['ok' => false, 'message' => 'Foyer introuvable pour cette demande.'];
        }

        if ($foyerTarget->getUser()?->getId() !== $accepteur->getId()) {
            return [
                'ok' => false,
                'message' => 'Seul le titulaire du foyer destinataire peut accepter cette fusion.',
            ];
        }

        $demandeur = $demande->getDemandeur();
        $this->reassignChildren($foyerSource, $foyerTarget);
        $this->rattacherDemandeurCommeCoparent($foyerTarget, $demandeur, $foyerSource);

        $demande->markUsed($accepteur);
        $this->em->flush();

        $this->deleteEmptyFoyer($foyerSource);

        return [
            'ok' => true,
            'message' => 'Les foyers ont été fusionnés avec succès !',
        ];
    }

    private function reassignChildren(Foyer $source, Foyer $target): void
    {
        // DQL sur le côté propriétaire pour éviter orphanRemoval lors du déplacement.
        $this->em->createQuery('UPDATE App\Entity\Danseur d SET d.foyer = :target WHERE d.foyer = :source')
            ->setParameter('target', $target)
            ->setParameter('source', $source)
            ->execute();

        $this->em->createQuery('UPDATE App\Entity\AchatGoodie a SET a.foyer = :target WHERE a.foyer = :source')
            ->setParameter('target', $target)
            ->setParameter('source', $source)
            ->execute();

        $this->em->createQuery('UPDATE App\Entity\ReservationCostume r SET r.foyer = :target WHERE r.foyer = :source')
            ->setParameter('target', $target)
            ->setParameter('source', $source)
            ->execute();

        $this->em->createQuery('UPDATE App\Entity\CommandeBoutique c SET c.foyer = :target WHERE c.foyer = :source')
            ->setParameter('target', $target)
            ->setParameter('source', $source)
            ->execute();

        $this->em->refresh($source);
        $this->em->refresh($target);
    }

    private function rattacherDemandeurCommeCoparent(Foyer $target, ?User $demandeur, Foyer $source): void
    {
        if (null === $demandeur || $demandeur->getId() === $target->getUser()?->getId()) {
            return;
        }

        $email = mb_strtolower(trim((string) $demandeur->getEmail()));
        if ($email === '') {
            return;
        }

        if (!$target->getParent2Email()) {
            $target->setParent2Email($email);
            $target->setParent2Nom($source->getNom());
            $target->setParent2Telephone($demandeur->getTelephone());
            $target->setParent2IsDifferent(true);
        }

        foreach ($target->getDanseurs() as $danseur) {
            if (!$danseur->getParent2Email()) {
                $danseur->setParent2Email($email);
            }
        }
    }

    /**
     * Supprime le foyer source sans cascade-remove du User titulaire
     * (le OneToOne Foyer→User a cascade remove côté ORM).
     */
    private function deleteEmptyFoyer(Foyer $source): void
    {
        $sourceId = $source->getId();
        if (null === $sourceId) {
            return;
        }

        $sourceUser = $source->getUser();
        if (null !== $sourceUser) {
            $sourceUser->setFoyer(null);
        }

        // Expire les demandes encore liées avant suppression SQL.
        $this->em->createQuery(
            'UPDATE App\Entity\DemandeFusionFoyer d SET d.expiresAt = :past
             WHERE (d.foyerSource = :f OR d.foyerTarget = :f) AND d.usedAt IS NULL'
        )
            ->setParameter('past', new \DateTimeImmutable('-1 second'))
            ->setParameter('f', $source)
            ->execute();

        $this->em->flush();
        $this->em->detach($source);

        $this->em->getConnection()->executeStatement(
            'DELETE FROM foyer WHERE id = ?',
            [$sourceId]
        );

        if (null !== $sourceUser) {
            $this->em->refresh($sourceUser);
            $sourceUser->setFoyer(null);
            $this->em->flush();
        }
    }
}
