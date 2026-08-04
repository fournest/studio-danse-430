<?php

namespace App\Controller;

use App\Entity\Costume;
use App\Entity\ReservationCostume;
use App\Entity\User;
use App\Enum\ModePaiementBoutique;
use App\Enum\StatutReservation;
use App\Form\CostumeReservationType;
use App\Repository\CostumeRepository;
use App\Service\CotisationCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CostumeController extends AbstractController
{
    private const GENRES = ['Homme', 'Femme', 'Enfant'];
    private const DRAFT_SESSION_KEY = 'costume_reservation_draft';
    private const CAUTION_EUR = 50.0;

    #[Route('/costumes', name: 'app_costumes_index', methods: ['GET'])]
    public function index(Request $request, CostumeRepository $costumeRepository): Response
    {
        $theme = $request->query->getString('theme') ?: null;
        $taille = $request->query->getString('taille') ?: null;
        $genre = $request->query->getString('genre') ?: null;

        if ($genre && !\in_array($genre, self::GENRES, true)) {
            $genre = null;
        }

        return $this->render('costume/index.html.twig', [
            'costumes' => $costumeRepository->findDisponiblesHorsGala($theme, $taille, $genre),
            'themes' => $costumeRepository->findDistinctThemesHorsGala(),
            'tailles' => $costumeRepository->findDistinctTaillesHorsGala(),
            'genres' => self::GENRES,
            'filters' => [
                'theme' => $theme,
                'taille' => $taille,
                'genre' => $genre,
            ],
        ]);
    }

    /** Alias historique — redirige vers la vitrine publique. */
    #[Route('/location-costumes', name: 'app_costume_index', methods: ['GET'])]
    public function indexLegacy(): Response
    {
        return $this->redirectToRoute('app_costumes_index', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/costumes/{id}/reserver', name: 'app_costumes_reserver', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[Route('/location-costumes/{id}/reserver', name: 'app_costume_reserver', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function reserver(
        Costume $costume,
        Request $request,
        SessionInterface $session,
    ): Response {
        if (!$costume->isDisponibleHorsGala()) {
            $this->addFlash('danger', 'Ce costume n’est pas disponible à la location hors gala.');

            return $this->redirectToRoute('app_costumes_index');
        }

        $reservation = new ReservationCostume();
        $reservation->setCostume($costume);
        $reservation->setUser($this->getUser());

        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();
        if ($foyer) {
            $reservation->setFoyer($foyer);
            $reservation->setSaison(CotisationCalculatorService::SAISON_COURANTE);
        }

        $tailles = $costume->getTaillesDisponibles();
        if (\count($tailles) === 1) {
            $reservation->setTaille($tailles[0]);
        }

        $form = $this->createForm(CostumeReservationType::class, $reservation, [
            'costume' => $costume,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($costume->getQuantite() < $reservation->getQuantite()) {
                $this->addFlash('danger', 'Désolé, la quantité demandée dépasse le stock disponible (' . $costume->getQuantite() . ' restant(s)).');

                return $this->redirectToRoute('app_costumes_reserver', ['id' => $costume->getId()]);
            }

            $prixUnitaire = $costume->getTarifLocationEffectif() ?? 0.0;
            $prixTotal = $prixUnitaire * $reservation->getQuantite();
            $reservation->setPrixTotal(number_format($prixTotal, 2, '.', ''));

            $session->set(self::DRAFT_SESSION_KEY, $this->serializeDraft($reservation, $costume));

            return $this->redirectToRoute('app_costumes_validation', ['id' => $costume->getId()]);
        }

        return $this->render('costume/reserver.html.twig', [
            'costume' => $costume,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/costumes/{id}/validation', name: 'app_costumes_validation', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function validation(
        Costume $costume,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
    ): Response {
        $draft = $session->get(self::DRAFT_SESSION_KEY);
        if (!\is_array($draft) || (int) ($draft['costumeId'] ?? 0) !== $costume->getId()) {
            $this->addFlash('warning', 'Veuillez d’abord remplir le formulaire de réservation.');

            return $this->redirectToRoute('app_costumes_reserver', ['id' => $costume->getId()]);
        }

        $prixTotal = (float) ($draft['prixTotal'] ?? 0);
        $modesPaiement = ModePaiementBoutique::cases();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('costume_validation' . $costume->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $mode = ModePaiementBoutique::tryFrom($request->request->getString('modePaiement'));
            if (!$mode) {
                $this->addFlash('danger', 'Choisissez un mode de règlement.');

                return $this->redirectToRoute('app_costumes_validation', ['id' => $costume->getId()]);
            }

            if ($costume->getQuantite() < (int) $draft['quantite']) {
                $session->remove(self::DRAFT_SESSION_KEY);
                $this->addFlash('danger', 'Stock insuffisant — la réservation n’a pas pu être finalisée.');

                return $this->redirectToRoute('app_costumes_index');
            }

            $reservation = $this->hydrateFromDraft($draft, $costume);
            $reservation->setModePaiementSouhaite($mode->value);
            $reservation->setStatut(StatutReservation::EN_ATTENTE);

            $costume->setQuantite($costume->getQuantite() - $reservation->getQuantite());

            $entityManager->persist($reservation);
            $entityManager->flush();
            $session->remove(self::DRAFT_SESSION_KEY);

            $this->addFlash('success', 'Votre demande de location a bien été enregistrée.');

            return $this->redirectToRoute('app_costumes_confirmation', ['id' => $reservation->getId()]);
        }

        return $this->render('costume/validation.html.twig', [
            'costume' => $costume,
            'draft' => $draft,
            'prixTotal' => $prixTotal,
            'caution' => self::CAUTION_EUR,
            'modesPaiement' => $modesPaiement,
        ]);
    }

    #[Route('/costumes/confirmation/{id}', name: 'app_costumes_confirmation', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function confirmation(ReservationCostume $reservation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($reservation->getUser()?->getId() !== $user->getId()
            && !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('costume/confirmation.html.twig', [
            'reservation' => $reservation,
            'caution' => self::CAUTION_EUR,
            'modePaiementLabel' => ModePaiementBoutique::tryFrom((string) $reservation->getModePaiementSouhaite())?->getLabel(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDraft(ReservationCostume $reservation, Costume $costume): array
    {
        return [
            'costumeId' => $costume->getId(),
            'taille' => $reservation->getTaille(),
            'quantite' => $reservation->getQuantite(),
            'dateEvenement' => $reservation->getDateEvenement()?->format('Y-m-d'),
            'dateDebut' => $reservation->getDateDebut()?->format('Y-m-d'),
            'dateFin' => $reservation->getDateFin()?->format('Y-m-d'),
            'modeLivraison' => $reservation->getModeLivraison()->value,
            'remarques' => $reservation->getRemarques(),
            'prixTotal' => $reservation->getPrixTotal(),
            'foyerId' => $reservation->getFoyer()?->getId(),
            'saison' => $reservation->getSaison(),
        ];
    }

    /**
     * @param array<string, mixed> $draft
     */
    private function hydrateFromDraft(array $draft, Costume $costume): ReservationCostume
    {
        /** @var User $user */
        $user = $this->getUser();

        $reservation = new ReservationCostume();
        $reservation->setCostume($costume);
        $reservation->setUser($user);
        $reservation->setTaille(isset($draft['taille']) ? (string) $draft['taille'] : null);
        $reservation->setQuantite(max(1, (int) ($draft['quantite'] ?? 1)));
        $reservation->setPrixTotal((string) ($draft['prixTotal'] ?? '0.00'));
        $reservation->setRemarques(isset($draft['remarques']) ? (string) $draft['remarques'] : null);
        $reservation->setSaison(isset($draft['saison']) ? (string) $draft['saison'] : null);

        if (!empty($draft['dateEvenement'])) {
            $reservation->setDateEvenement(new \DateTime((string) $draft['dateEvenement']));
        }
        $reservation->setDateDebut(new \DateTime((string) $draft['dateDebut']));
        $reservation->setDateFin(new \DateTime((string) $draft['dateFin']));

        if (!empty($draft['modeLivraison'])) {
            $mode = \App\Enum\ModeLivraison::tryFrom((string) $draft['modeLivraison']);
            if ($mode) {
                $reservation->setModeLivraison($mode);
            }
        }

        $foyer = $user->getFoyer();
        if ($foyer) {
            $reservation->setFoyer($foyer);
        }

        return $reservation;
    }
}
