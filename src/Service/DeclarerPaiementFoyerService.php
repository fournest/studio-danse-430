<?php

namespace App\Service;

use App\Entity\Foyer;
use App\Entity\Paiement;
use App\Enum\ModePaiement;

/**
 * Déclaration de paiement par la famille (statut PAIEMENT_DECLARE).
 */
final class DeclarerPaiementFoyerService
{
    public function declarer(
        Foyer $foyer,
        string $saison,
        ModePaiement $mode,
        float $montant,
        ?string $reference = null,
    ): Paiement {
        $montant = round($montant, 2);
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant déclaré doit être strictement positif.');
        }

        $resteAPayer = $foyer->getResteAPayer($saison);
        if ($montant > $resteAPayer + 0.009) {
            throw new \InvalidArgumentException(sprintf(
                'Le montant déclaré (%s €) excède le reste à payer (%s €).',
                number_format($montant, 2, ',', ' '),
                number_format($resteAPayer, 2, ',', ' ')
            ));
        }

        $inscriptions = $foyer->getInscriptions($saison);
        if ($inscriptions === []) {
            throw new \RuntimeException('Aucune inscription trouvée pour cette saison.');
        }

        foreach ($inscriptions as $inscription) {
            foreach ($inscription->getPaiements() as $paiement) {
                if ($paiement->canBeDeclaredByFamille()
                    && abs($paiement->getMontant() - $montant) < 0.009) {
                    $paiement->marquerDeclare($mode, $reference);
                    $inscription->refreshStatutPaiement();

                    return $paiement;
                }
            }
        }

        $cible = null;
        $maxReste = 0.0;
        foreach ($inscriptions as $inscription) {
            $reste = $inscription->getResteAPayer();
            if ($reste >= $maxReste) {
                $maxReste = $reste;
                $cible = $inscription;
            }
        }

        if (null === $cible) {
            $cible = $inscriptions[0];
        }

        $paiement = new Paiement();
        $paiement->setMontant($montant);
        $paiement->setMode($mode);
        $paiement->setDateEncaissementPrevue(new \DateTimeImmutable('today'));
        $paiement->marquerDeclare($mode, $reference);
        $cible->addPaiement($paiement);
        $cible->refreshStatutPaiement();

        return $paiement;
    }
}
