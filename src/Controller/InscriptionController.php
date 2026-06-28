<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use App\Form\InscriptionType;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\User; // 🔌 Import requis pour le Webhook

#[IsGranted('ROLE_USER')] // 🔒 Personne ne peut accéder au tunnel d'inscription s'il n'est pas connecté
final class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CoursRepository $coursRepository,
        HttpClientInterface $httpClient, // 🟢 On s'arrête ici pour les arguments !
        LoggerInterface $logger,
    ): Response {
        
        // 🟢 ÉTAPE 1 : On récupère proprement l'utilisateur connecté à l'intérieur de la méthode
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $inscription = new Inscription();

        // 🔒 Sécurité : On passe l'utilisateur actuel au formulaire
        $form = $this->createForm(InscriptionType::class, $inscription, [
            'user' => $user,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coursSelectionnes = $form->get('cours')->getData();
            $inscriptionsCreees = [];
            $detailsCoursPayload = [];

            foreach ($coursSelectionnes as $cours) {
                $ligne = new Inscription();
                $ligne->setDanseur($inscription->getDanseur());
                $ligne->setCours($cours);
                $ligne->setSaison($inscription->getSaison());
                $ligne->setCertificatMedical($inscription->getCertificatMedical());

                // On initialise proprement les Enums par défaut
                $ligne->setStatutDossier(StatutDossier::EN_ATTENTE);
                $ligne->setStatutPaiement(StatutPaiement::NON_PAYE);

                $entityManager->persist($ligne);
                $inscriptionsCreees[] = $ligne;

                // Remplissage des détails pour n8n
                $detailsCoursPayload[] = [
                    'id' => $cours->getId(),
                    'nom' => $cours->getNom(),
                    'jour' => $cours->getJour(),
                    'heure' => $cours->getHeure() ? $cours->getHeure()->format('H:i') : null,
                    'professeur' => $cours->getProfesseur()
                ];
            }

            $entityManager->flush();

            // =====================================================================
            // 🚀 ENVOI DU WEBHOOK AUTOMATION (n8n)
            // =====================================================================
            $n8nWebhookUrl = $_ENV['N8N_WEBHOOK_URL'] ?? $_SERVER['N8N_WEBHOOK_URL'] ?? getenv('N8N_WEBHOOK_URL') ?? null;

            if ($n8nWebhookUrl) {
                try {
                    // Payload complet structuré pour tes noeuds n8n
                    $response = $httpClient->request('POST', $n8nWebhookUrl, [
                        'json' => [
                            'evenement' => 'nouvelle_inscription',
                            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                            'foyer' => [
                                'parent_id' => $user->getId(),
                                'parent_email' => $user->getUserIdentifier(),
                            ],
                            'danseur' => [
                                'id' => $inscription->getDanseur()->getId(),
                                'prenom' => $inscription->getDanseur()->getPrenom(),
                                'nom' => $inscription->getDanseur()->getNom(),
                                'date_naissance' => $inscription->getDanseur()->getDateNaissance() ? $inscription->getDanseur()->getDateNaissance()->format('Y-m-d') : null,
                            ],
                            'saison' => $inscription->getSaison(),
                            'nombre_cours' => count($inscriptionsCreees),
                            'cours_selectionnes' => $detailsCoursPayload
                        ],
                    ]);

                    // 🔥 LE FIX : On force l'exécution immédiate en lisant le statut de la réponse
                    // Symfony va bloquer le script un millième de seconde, juste le temps que n8n reçoive le JSON
                    $statusCode = $response->getStatusCode();

                    if ($statusCode !== 200) {
                        $logger->error('n8n a répondu avec un code d\'erreur : ' . $statusCode);
                        $this->addFlash('error', 'Le serveur de notification a retourné une erreur.');
                    }

                } catch (\Exception $e) {
                    $logger->error('Echec envoi Webhook n8n : ' . $e->getMessage());
                    $this->addFlash('error', 'Impossible de joindre n8n : ' . $e->getMessage());
                }
            }

            // =====================================================================
            //   PROCHAINE ÉTAPE : Appel API HelloAsso (Checkout génération)
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
            'form' => $form->createView(),
        ]);
    }
}
