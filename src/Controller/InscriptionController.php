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

#[IsGranted('ROLE_USER')]
final class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CoursRepository $coursRepository,
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
    ): Response {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        // 🛡️ SÉCURITÉ CRITIQUE : Interdiction d'accéder aux inscriptions sans dossier familial configuré
        if (!$foyer) {
            $this->addFlash('error', 'Vous devez d’abord configurer votre dossier familial (Foyer) avant d’inscrire un danseur.');
            return $this->redirectToRoute('app_foyer_new');
        }

        // 🛡️ SÉCURITÉ UX : Si le foyer existe mais qu'il n'y a aucun danseur dedans
        if ($foyer->getDanseurs()->isEmpty()) {
            $this->addFlash('error', 'Votre foyer ne contient aucun danseur. Veuillez ajouter un élève avant de l’inscrire aux cours.');
            return $this->redirectToRoute('app_foyer_add');
        }

        $inscription = new Inscription();

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

                $ligne->setStatutDossier(StatutDossier::EN_ATTENTE);
                $ligne->setStatutPaiement(StatutPaiement::NON_PAYE);

                $entityManager->persist($ligne);
                $inscriptionsCreees[] = $ligne;

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
            // 🚀 ENVOI DU WEBHOOK AUTOMATION (n8n) ENRICHI
            // =====================================================================
            $n8nWebhookUrl = $_ENV['N8N_WEBHOOK_URL'] ?? $_SERVER['N8N_WEBHOOK_URL'] ?? getenv('N8N_WEBHOOK_URL') ?? null;

            if ($n8nWebhookUrl) {
                try {
                    $response = $httpClient->request('POST', $n8nWebhookUrl, [
                        'json' => [
                            'evenement' => 'nouvelle_inscription',
                            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                            'foyer' => [
                                'parent_id' => $user->getId(),
                                'parent_email' => $user->getUserIdentifier(),
                                'parent_telephone' => $user->getTelephone(),
                                'nom_foyer' => $foyer->getNom(), // ✨ On pioche directement le nom propre du Foyer
                                'adresse' => sprintf('%s, %s %s', $foyer->getAdresse(), $foyer->getCodePostal(), $foyer->getVille()),
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

                    $statusCode = $response->getStatusCode();

                    if ($statusCode !== 200) {
                        $logger->error('n8n a répondu avec un code d\'erreur : ' . $statusCode);
                    }
                } catch (\Exception $e) {
                    $logger->error('Echec envoi Webhook n8n : ' . $e->getMessage());
                }
            }

            // =====================================================================
            // PROCHAINE ÉTAPE : Appel API HelloAsso (Checkout génération)
            // =====================================================================

            $this->addFlash(
                'success',
                sprintf(
                    'Félicitations, les %d inscription(s) de %s ont bien été enregistrées !',
                    count($inscriptionsCreees),
                    $inscription->getDanseur()->getPrenom()
                )
            );

            // 🔄 Redirection propre vers le tableau de bord familial
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('inscription/index.html.twig', [
            'cours' => $coursRepository->findAllOrdered(),
            'form' => $form->createView(),
        ]);
    }
}
