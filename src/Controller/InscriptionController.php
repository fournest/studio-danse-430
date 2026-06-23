<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use App\Form\InscriptionType;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CoursRepository $coursRepository,
    ): Response {
        $inscription = new Inscription();
        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coursSelectionnes = $form->get('cours')->getData();

            $inscriptionsCreees = [];

            foreach ($coursSelectionnes as $cours) {
                $ligne = new Inscription();
                $ligne->setDanseur($inscription->getDanseur());
                $ligne->setCours($cours);
                $ligne->setSaison($inscription->getSaison());
                $ligne->setCertificatMedical($inscription->getCertificatMedical());

                // Valeurs par défaut : le dossier et le paiement sont gérés ensuite
                // depuis le back-office (et/ou via HelloAsso).
                $ligne->setStatutDossier(StatutDossier::EN_ATTENTE);
                $ligne->setStatutPaiement(StatutPaiement::NON_PAYE);

                $entityManager->persist($ligne);
                $inscriptionsCreees[] = $ligne;
            }

            $entityManager->flush();

            // =====================================================================
            // POINT D'INTÉGRATION FUTUR — NE PAS SUPPRIMER
            // ---------------------------------------------------------------------
            // À cet endroit, une fois les inscriptions enregistrées en base, on
            // viendra brancher les automatisations externes :
            //
            //   1. Webhook n8n :
            //      - Notifier n8n de la/des nouvelle(s) inscription(s)
            //        (ex: HttpClient POST vers l'URL stockée dans %env(N8N_WEBHOOK_URL)%).
            //      - Payload conseillé : danseur, liste des cours, saison, statuts.
            //
            //   2. Appel API HelloAsso :
            //      - Créer/initialiser le paiement (checkout) pour la cotisation.
            //      - Récupérer l'identifiant de paiement et le stocker via
            //        $ligne->setHelloAssoPaymentId(...), puis re-flush.
            //      - Rediriger éventuellement l'utilisateur vers l'URL de paiement.
            //
            // Variables d'environnement prévues (cf. .env / .env.local) :
            //   N8N_WEBHOOK_URL, HELLOASSO_CLIENT_ID, HELLOASSO_CLIENT_SECRET, ...
            //
            // Exemple de squelette (à implémenter plus tard) :
            //   $this->n8nNotifier->notifyInscription($inscriptionsCreees);
            //   $checkoutUrl = $this->helloAsso->createCheckout($inscriptionsCreees);
            //   return $this->redirect($checkoutUrl);
            // =====================================================================

            $this->addFlash(
                'success',
                sprintf(
                    '%d inscription(s) enregistrée(s) avec succès. Notre équipe vous recontactera prochainement.',
                    count($inscriptionsCreees)
                )
            );

            return $this->redirectToRoute('app_inscription');
        }

        return $this->render('inscription/index.html.twig', [
            'cours' => $coursRepository->findAllOrdered(),
            'form' => $form,
        ]);
    }
}
