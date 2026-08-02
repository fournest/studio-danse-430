<?php

namespace App\Controller;

use App\Dto\CotisationDetail;
use App\Entity\User;
use App\Entity\Foyer;
use App\Entity\Danseur;
use App\Entity\Inscription;
use App\Entity\Paiement;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use App\Enum\ModePaiement;
use App\Enum\StatutInscription;
use App\Enum\StatutPaiement as StatutLignePaiement;
use App\Enum\StatutSante;
use App\Form\FoyerType;
use App\Form\DanseurType;
use App\Repository\CoursRepository;
use App\Repository\DanseurRepository;
use App\Repository\InscriptionRepository;
use App\Security\Voter\InscriptionTunnelVoter;
use App\Service\CotisationCalculatorService;
use App\Service\EchelonnementService;
use App\Service\InscriptionConfirmationMailer;
use App\Service\QsSportQuestionnaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/mon-foyer')]
#[IsGranted('ROLE_USER')]
class FoyerController extends AbstractController
{
    public function __construct(
        private readonly string $helloAssoCampaignUrl = '',
    ) {
    }
    #[Route('', name: 'app_foyer_index', methods: ['GET'])]
    public function index(DanseurRepository $danseurRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        $danseursProprietaire = $foyer ? $foyer->getDanseurs()->toArray() : [];
        $danseursRattaches = $danseurRepository->findAccessibleByParent2Email((string) $user->getEmail());

        $byId = [];
        foreach (array_merge($danseursProprietaire, $danseursRattaches) as $danseur) {
            $byId[$danseur->getId()] = $danseur;
        }
        $allDanseurs = array_values($byId);

        if (!$foyer && empty($allDanseurs)) {
            return $this->redirectToRoute('app_foyer_new');
        }

        return $this->render('foyer/index.html.twig', [
            'foyer' => $foyer,
            'danseurs' => $allDanseurs,
            'isFoyerOwner' => $foyer !== null,
            'ownedFoyerId' => $foyer?->getId(),
        ]);
    }

    #[Route('/configuration', name: 'app_foyer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getFoyer()) {
            return $this->redirectToRoute('app_foyer_index');
        }

        $foyer = new Foyer();
        $form = $this->createForm(FoyerType::class, $foyer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $foyer->setUser($user);
            $user->setFoyer($foyer);

            $em->persist($foyer);
            $em->flush();

            $this->addFlash('success', 'Votre dossier familial a bien été configuré ! Vous pouvez maintenant ajouter vos danseurs.');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Configuration du dossier familial (Foyer)'
        ]);
    }

    #[Route('/ajouter-un-danseur', name: 'app_foyer_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        if (!$foyer) {
            $this->addFlash('error', 'Vous devez d’abord configurer votre foyer principal.');
            return $this->redirectToRoute('app_foyer_new');
        }

        $danseur = new Danseur();
        $form = $this->createForm(DanseurType::class, $danseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $danseur->setFoyer($foyer);

            $em->persist($danseur);
            $em->flush();

            $this->addFlash('success', $danseur->getPrenom() . ' a bien été ajouté(e) au foyer !');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter un membre au foyer'
        ]);
    }

    #[Route('/danseur/{id}', name: 'app_foyer_show', methods: ['GET'])]
    public function show(Danseur $danseur): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canViewDanseur($danseur, $user)) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette fiche.");
        }

        return $this->render('foyer/show.html.twig', [
            'danseur' => $danseur,
            'isReadOnly' => !$this->isPrimaryParent($danseur, $user),
        ]);
    }

    #[Route('/modifier-un-danseur/{id}', name: 'app_foyer_edit', methods: ['GET', 'POST'])]
    public function edit(Danseur $danseur, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isPrimaryParent($danseur, $user)) {
            $this->addFlash('warning', 'Vous êtes en mode lecture seule sur ce profil.');
            return $this->redirectToRoute('app_foyer_show', ['id' => $danseur->getId()]);
        }

        $form = $this->createForm(DanseurType::class, $danseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Le profil de ' . $danseur->getPrenom() . ' a été mis à jour.');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le profil de ' . $danseur->getPrenom()
        ]);
    }

    #[Route('/inscription-cours', name: 'app_foyer_inscription_cours', methods: ['GET', 'POST'])]
    public function inscriptionCours(
        Request $request,
        EntityManagerInterface $em,
        CoursRepository $coursRepository,
        CotisationCalculatorService $calculator,
        InscriptionRepository $inscriptionRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        if (!$foyer) {
            $this->addFlash('warning', 'Seuls les parents titulaires d’un foyer peuvent gérer les inscriptions aux cours.');
            return $this->redirectToRoute('app_foyer_index');
        }

        if ($foyer->getDanseurs()->isEmpty()) {
            $this->addFlash('error', 'Ajoutez au moins un danseur avant de choisir les cours.');
            return $this->redirectToRoute('app_foyer_add');
        }

        $saison = CotisationCalculatorService::SAISON_COURANTE;
        if ($locked = $this->findLockedInscriptionFoyer($foyer, $saison)) {
            return $this->redirectToRoute('app_foyer_inscription_confirmation', ['id' => $locked->getId()]);
        }
        $allCours = $coursRepository->findAllOrdered();
        $danseurs = $foyer->getDanseurs()->toArray();

        // Sélection initiale : inscriptions saison courante
        $selectionByDanseur = [];
        $attenteByDanseur = [];
        foreach ($danseurs as $danseur) {
            $selectedIds = [];
            $attenteIds = [];
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison || !$inscription->getCours()) {
                    continue;
                }
                $coursId = $inscription->getCours()->getId();
                if ($inscription->isEstEnListeDAttente()) {
                    $attenteIds[] = $coursId;
                } else {
                    $selectedIds[] = $coursId;
                }
            }
            $selectionByDanseur[$danseur->getId()] = $selectedIds;
            $attenteByDanseur[$danseur->getId()] = $attenteIds;
        }

        $action = $request->request->get('_action', 'preview');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('inscription_cours', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $posted = $request->request->all('cours') ?? [];
            $postedAttente = $request->request->all('attente') ?? [];
            $selectionByDanseur = [];
            $attenteByDanseur = [];
            foreach ($danseurs as $danseur) {
                $rawIds = $posted[(string) $danseur->getId()] ?? [];
                if (!is_array($rawIds)) {
                    $rawIds = [];
                }
                $rawAttente = $postedAttente[(string) $danseur->getId()] ?? [];
                if (!is_array($rawAttente)) {
                    $rawAttente = [];
                }
                $selectionByDanseur[$danseur->getId()] = array_map('intval', $rawIds);
                $attenteByDanseur[$danseur->getId()] = array_map('intval', $rawAttente);
            }

            if ($action === 'save') {
                $forcedWaitlist = $this->persistCourseSelection(
                    $em,
                    $inscriptionRepository,
                    $foyer,
                    $danseurs,
                    $allCours,
                    $selectionByDanseur,
                    $attenteByDanseur,
                    $saison,
                );
                $em->flush();

                // Re-synchronise les tableaux après éventuelles bascules auto en liste d'attente
                $selectionByDanseur = [];
                $attenteByDanseur = [];
                foreach ($danseurs as $danseur) {
                    $selectedIds = [];
                    $attenteIds = [];
                    foreach ($danseur->getInscriptions() as $inscription) {
                        if ($inscription->getSaison() !== $saison || !$inscription->getCours()) {
                            continue;
                        }
                        $coursId = $inscription->getCours()->getId();
                        if ($inscription->isEstEnListeDAttente()) {
                            $attenteIds[] = $coursId;
                        } else {
                            $selectedIds[] = $coursId;
                        }
                    }
                    $selectionByDanseur[$danseur->getId()] = $selectedIds;
                    $attenteByDanseur[$danseur->getId()] = $attenteIds;
                }

                $detail = $calculator->calculateForFoyer($foyer, $saison);
                $this->applyMontantsToInscriptions($foyer, $saison, $detail);
                $em->flush();

                if ($forcedWaitlist !== []) {
                    $this->addFlash(
                        'warning',
                        'Certains cours étaient complets : inscription placée en liste d’attente (non facturée) — '
                        . implode(', ', $forcedWaitlist) . '.'
                    );
                }

                $this->addFlash(
                    'success',
                    sprintf(
                        'Inscriptions enregistrées. Total cotisation saison %s : %s €.',
                        $saison,
                        number_format($detail->total, 2, ',', ' ')
                    )
                );

                $nextPaiement = $this->findFirstInscriptionSansPaiement($foyer, $saison);
                if (null !== $nextPaiement) {
                    return $this->redirectToRoute('app_foyer_inscription_paiement', ['id' => $nextPaiement->getId()]);
                }

                $firstInscription = $this->findFirstInscriptionSaison($foyer, $saison);
                if (null !== $firstInscription) {
                    return $this->redirectToRoute('app_foyer_inscription_sante', ['id' => $firstInscription->getId()]);
                }

                return $this->redirectToRoute('app_foyer_index');
            }
        }

        $coursById = [];
        foreach ($allCours as $cours) {
            $coursById[$cours->getId()] = $cours;
        }

        $placesParCours = [];
        foreach ($allCours as $cours) {
            $placesParCours[$cours->getId()] = [
                'restantes' => $cours->getPlacesRestantes($saison),
                'complet' => $cours->estComplet($saison),
                'inscrits' => $cours->getNombreInscrits($saison),
                'capacite' => $cours->getCapaciteMax(),
            ];
        }

        $selectionForCalc = [];
        $coursParDanseur = [];
        foreach ($danseurs as $danseur) {
            $eligible = [];
            $ineligible = [];
            foreach ($allCours as $cours) {
                if ($cours->isEligibleForDanseur($danseur)) {
                    $eligible[] = $cours;
                } else {
                    $ineligible[] = $cours;
                }
            }
            $coursParDanseur[$danseur->getId()] = [
                'eligible' => $eligible,
                'ineligible' => $ineligible,
            ];

            $selectedCours = [];
            $attenteIds = $attenteByDanseur[$danseur->getId()] ?? [];
            $normalIds = $selectionByDanseur[$danseur->getId()] ?? [];
            $allSelectedIds = array_values(array_unique(array_merge($normalIds, $attenteIds)));
            foreach ($allSelectedIds as $coursId) {
                if (!isset($coursById[$coursId])) {
                    continue;
                }
                $cours = $coursById[$coursId];
                if (!$cours->isEligibleForDanseur($danseur)) {
                    continue;
                }
                $selectedCours[] = $cours;
            }
            $selectionForCalc[] = [
                'danseur' => $danseur,
                'cours' => $selectedCours,
                'attenteIds' => $attenteIds,
            ];
        }

        $cotisation = $calculator->calculate($selectionForCalc, $foyer, $saison);

        return $this->render('foyer/inscription_cours.html.twig', [
            'foyer' => $foyer,
            'danseurs' => $danseurs,
            'coursParDanseur' => $coursParDanseur,
            'selectionByDanseur' => $selectionByDanseur,
            'attenteByDanseur' => $attenteByDanseur,
            'placesParCours' => $placesParCours,
            'cotisation' => $cotisation,
            'saison' => $saison,
        ]);
    }

    /**
     * @param list<Danseur> $danseurs
     * @param list<\App\Entity\Cours> $allCours
     * @param array<int, list<int>> $selectionByDanseur
     * @param array<int, list<int>> $attenteByDanseur
     *
     * @return list<string> libellés des cours basculés automatiquement en liste d'attente
     */
    private function persistCourseSelection(
        EntityManagerInterface $em,
        InscriptionRepository $inscriptionRepository,
        Foyer $foyer,
        array $danseurs,
        array $allCours,
        array $selectionByDanseur,
        array $attenteByDanseur,
        string $saison,
    ): array {
        $coursById = [];
        foreach ($allCours as $cours) {
            $coursById[$cours->getId()] = $cours;
        }

        $forcedWaitlistLabels = [];
        /** @var array<int, int> $extraSeatsTaken coursId => nb places prises dans cette requête */
        $extraSeatsTaken = [];

        // Pass 1 : libérer les places désélectionnées (flush pour un comptage SQL fiable)
        foreach ($danseurs as $danseur) {
            if ($danseur->getFoyer()?->getId() !== $foyer->getId()) {
                continue;
            }

            $wantedNormal = array_values(array_unique($selectionByDanseur[$danseur->getId()] ?? []));
            $wantedAttente = array_values(array_unique($attenteByDanseur[$danseur->getId()] ?? []));
            $wantedAttente = array_values(array_diff($wantedAttente, $wantedNormal));

            $filterEligible = static function (array $ids) use ($coursById, $danseur): array {
                return array_values(array_filter(
                    $ids,
                    static function (int $id) use ($coursById, $danseur): bool {
                        if (!isset($coursById[$id])) {
                            return false;
                        }

                        return $coursById[$id]->isEligibleForDanseur($danseur);
                    }
                ));
            };
            $wantedAll = array_values(array_unique(array_merge(
                $filterEligible($wantedNormal),
                $filterEligible($wantedAttente)
            )));

            foreach ($danseur->getInscriptions()->toArray() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                $coursId = $inscription->getCours()?->getId();
                if (null === $coursId || !\in_array($coursId, $wantedAll, true)) {
                    if ($inscription->getCours()) {
                        $danseur->removeCours($inscription->getCours());
                    }
                    $danseur->removeInscription($inscription);
                    $em->remove($inscription);
                }
            }
        }
        $em->flush();

        // Pass 2 : créer / mettre à jour avec contrôle de capacité
        foreach ($danseurs as $danseur) {
            if ($danseur->getFoyer()?->getId() !== $foyer->getId()) {
                continue;
            }

            $wantedNormal = array_values(array_unique($selectionByDanseur[$danseur->getId()] ?? []));
            $wantedAttente = array_values(array_unique($attenteByDanseur[$danseur->getId()] ?? []));
            $wantedAttente = array_values(array_diff($wantedAttente, $wantedNormal));

            $filterEligible = static function (array $ids) use ($coursById, $danseur): array {
                return array_values(array_filter(
                    $ids,
                    static function (int $id) use ($coursById, $danseur): bool {
                        if (!isset($coursById[$id])) {
                            return false;
                        }

                        return $coursById[$id]->isEligibleForDanseur($danseur);
                    }
                ));
            };
            $wantedNormal = $filterEligible($wantedNormal);
            $wantedAttente = $filterEligible($wantedAttente);
            $wantedAll = array_values(array_unique(array_merge($wantedNormal, $wantedAttente)));

            /** @var array<int, Inscription> $existingByCoursId */
            $existingByCoursId = [];
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() === $saison && $inscription->getCours()) {
                    $existingByCoursId[$inscription->getCours()->getId()] = $inscription;
                }
            }

            foreach ($wantedAll as $coursId) {
                $cours = $coursById[$coursId];
                $danseur->addCours($cours);
                $forceAttente = \in_array($coursId, $wantedAttente, true);
                $existing = $existingByCoursId[$coursId] ?? null;

                if (!$forceAttente) {
                    $excludeIds = [];
                    if ($existing && !$existing->isEstEnListeDAttente() && $existing->getId()) {
                        $excludeIds[] = $existing->getId();
                    }
                    $occupied = $inscriptionRepository->countOccupants($cours, $saison, $excludeIds)
                        + ($extraSeatsTaken[$coursId] ?? 0);
                    if ($occupied >= $cours->getCapaciteMax()) {
                        $forceAttente = true;
                        $forcedWaitlistLabels[] = sprintf('%s (%s)', $danseur->getPrenom(), $cours->getNom());
                    }
                }

                if ($existing) {
                    $wasAttente = $existing->isEstEnListeDAttente();
                    $existing->setEstEnListeDAttente($forceAttente);
                    if (!$forceAttente && $wasAttente) {
                        $extraSeatsTaken[$coursId] = ($extraSeatsTaken[$coursId] ?? 0) + 1;
                    }
                    continue;
                }

                $inscription = new Inscription();
                $inscription->setDanseur($danseur);
                $inscription->setCours($cours);
                $inscription->setSaison($saison);
                $inscription->setStatut(StatutInscription::BROUILLON);
                $inscription->setStatutDossier(StatutDossier::EN_ATTENTE);
                $inscription->setStatutPaiement(StatutPaiement::NON_PAYE);
                $inscription->setEstEnListeDAttente($forceAttente);
                $em->persist($inscription);

                if (!$forceAttente) {
                    $extraSeatsTaken[$coursId] = ($extraSeatsTaken[$coursId] ?? 0) + 1;
                }
            }
        }

        return array_values(array_unique($forcedWaitlistLabels));
    }

    #[Route('/inscription/{id}/paiement', name: 'app_foyer_inscription_paiement', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function inscriptionPaiement(
        Inscription $inscription,
        Request $request,
        EntityManagerInterface $em,
        EchelonnementService $echelonnementService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $danseur = $inscription->getDanseur();

        if (null === $danseur || !$this->isPrimaryParent($danseur, $user)) {
            throw $this->createAccessDeniedException('Vous n’avez pas accès à cette étape de paiement.');
        }

        if (!$this->isGranted(InscriptionTunnelVoter::EDIT_TUNNEL, $inscription)) {
            return $this->redirectToRoute('app_foyer_inscription_confirmation', ['id' => $inscription->getId()]);
        }

        $montantTotal = $inscription->getMontantTotal() ?? 0.0;
        $resteAPayer = $inscription->getResteAPayer();
        if ($resteAPayer <= 0 && $montantTotal <= 0) {
            $this->addFlash('warning', 'Aucun montant à régler pour cette inscription.');
            return $this->redirectToRoute('app_foyer_index');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('inscription_paiement' . $inscription->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $type = (string) $request->request->get('type_reglement', 'cheques');

            try {
                if ($type === 'cheques') {
                    $this->handleReglementCheques($request, $inscription, $echelonnementService, $resteAPayer > 0 ? $resteAPayer : $montantTotal);
                } elseif ($type === 'mixte') {
                    $this->handleReglementMixte($request, $inscription, $resteAPayer > 0 ? $resteAPayer : $montantTotal);
                } else {
                    throw new \InvalidArgumentException('Mode de règlement inconnu.');
                }

                $inscription->refreshStatutPaiement();
                $em->flush();

                $this->addFlash('success', 'Vos moyens de paiement ont bien été enregistrés. Le bureau procédera à l’encaissement.');

                $foyer = $danseur->getFoyer();
                $next = $foyer ? $this->findFirstInscriptionSansPaiement($foyer, $inscription->getSaison()) : null;
                if (null !== $next && $next->getId() !== $inscription->getId()) {
                    return $this->redirectToRoute('app_foyer_inscription_paiement', ['id' => $next->getId()]);
                }

                return $this->redirectToRoute('app_foyer_inscription_sante', ['id' => $inscription->getId()]);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $datesParOption = [];
        foreach ([1, 3, 10] as $n) {
            $datesParOption[$n] = array_map(
                static fn (\DateTimeImmutable $d) => $d->format('d/m/Y'),
                $echelonnementService->genererDatesEncaissement($inscription->getSaison(), $n)
            );
        }

        return $this->render('foyer/inscription_paiement.html.twig', [
            'inscription' => $inscription,
            'danseur' => $danseur,
            'montantTotal' => $montantTotal,
            'resteAPayer' => $resteAPayer > 0 ? $resteAPayer : $montantTotal,
            'datesParOption' => $datesParOption,
            'modesMixte' => array_filter(
                ModePaiement::cases(),
                static fn (ModePaiement $m) => $m !== ModePaiement::CHEQUE
            ),
            'modesAll' => ModePaiement::cases(),
            'paiementsExistants' => $inscription->getPaiements(),
        ]);
    }

    #[Route('/inscription/{id}/sante', name: 'app_foyer_inscription_sante', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function inscriptionSante(
        Inscription $inscription,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $danseurRef = $inscription->getDanseur();
        $foyer = $danseurRef?->getFoyer();

        if (null === $danseurRef || null === $foyer || !$this->isPrimaryParent($danseurRef, $user)) {
            throw $this->createAccessDeniedException('Vous n’avez pas accès à cette étape santé.');
        }

        if (!$this->isGranted(InscriptionTunnelVoter::EDIT_TUNNEL, $inscription)) {
            return $this->redirectToRoute('app_foyer_inscription_confirmation', ['id' => $inscription->getId()]);
        }

        $saison = $inscription->getSaison();
        $danseurs = $this->danseursInscritsPourSaison($foyer, $saison);
        if ($danseurs === []) {
            $this->addFlash('warning', 'Aucun danseur inscrit pour cette saison.');
            return $this->redirectToRoute('app_foyer_index');
        }

        $questions = QsSportQuestionnaire::questions();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('inscription_sante' . $inscription->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $errors = [];
            $santeData = $request->request->all('sante') ?? [];
            $files = $request->files->all('certificat') ?? [];

            foreach ($danseurs as $danseur) {
                $id = (string) $danseur->getId();
                $payload = \is_array($santeData[$id] ?? null) ? $santeData[$id] : [];
                $file = $files[$id] ?? null;
                $file = $file instanceof UploadedFile ? $file : null;

                try {
                    $this->processSanteDanseur($danseur, $payload, $file, $questions, $validator);
                } catch (\InvalidArgumentException $e) {
                    $errors[] = sprintf('%s : %s', $danseur->getPrenom(), $e->getMessage());
                }
            }

            if ($errors !== []) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            } else {
                $em->flush();
                $this->addFlash('success', 'Informations santé enregistrées pour tous les danseurs.');
                return $this->redirectToRoute('app_foyer_inscription_confirmation', ['id' => $inscription->getId()]);
            }
        }

        return $this->render('foyer/inscription_sante.html.twig', [
            'inscription' => $inscription,
            'foyer' => $foyer,
            'danseurs' => $danseurs,
            'saison' => $saison,
            'questions' => $questions,
        ]);
    }

    /**
     * @param list<array{id: string, section: string, label: string}> $questions
     * @param array<string, mixed> $payload
     */
    private function processSanteDanseur(
        Danseur $danseur,
        array $payload,
        ?UploadedFile $file,
        array $questions,
        ValidatorInterface $validator,
    ): void {
        $remarque = trim((string) ($payload['remarque'] ?? ''));
        $danseur->setRemarqueSante($remarque !== '' ? $remarque : null);

        $fileConstraint = new Assert\File(
            maxSize: '5M',
            mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'],
            mimeTypesMessage: 'Formats acceptés : PDF, JPG, PNG (max 5 Mo).',
        );

        if ($danseur->isMajeur()) {
            if (null === $file && !$danseur->getCertificatFilename()) {
                throw new \InvalidArgumentException('Un certificat médical (moins de 3 ans) est obligatoire pour un adhérent majeur.');
            }
            if (null !== $file) {
                $violations = $validator->validate($file, $fileConstraint);
                if (\count($violations) > 0) {
                    throw new \InvalidArgumentException((string) $violations->get(0)->getMessage());
                }
                $danseur->setCertificatFile($file);
            }
            $danseur->setAttestationQsSportValide(false);
            $danseur->setDateSignatureQsSport(null);
            if ($danseur->getStatutSante() !== StatutSante::VALIDE_BUREAU) {
                $danseur->setStatutSante(StatutSante::CERTIFICAT_FOURNI);
            }

            return;
        }

        // --- Mineur : QS-Sport ---
        $reponses = \is_array($payload['qs'] ?? null) ? $payload['qs'] : [];
        $hasOui = false;
        foreach ($questions as $q) {
            $val = $reponses[$q['id']] ?? null;
            if ($val !== 'oui' && $val !== 'non') {
                throw new \InvalidArgumentException(sprintf('Répondez à toutes les questions du QS-Sport (manque : « %s »).', $q['label']));
            }
            if ($val === 'oui') {
                $hasOui = true;
            }
        }

        if ($hasOui) {
            if (null === $file && !$danseur->getCertificatFilename()) {
                throw new \InvalidArgumentException('Au moins une réponse OUI au QS-Sport : un certificat médical est obligatoire.');
            }
            if (null !== $file) {
                $violations = $validator->validate($file, $fileConstraint);
                if (\count($violations) > 0) {
                    throw new \InvalidArgumentException((string) $violations->get(0)->getMessage());
                }
                $danseur->setCertificatFile($file);
            }
            $danseur->setAttestationQsSportValide(false);
            $danseur->setDateSignatureQsSport(null);
            if ($danseur->getStatutSante() !== StatutSante::VALIDE_BUREAU) {
                $danseur->setStatutSante(StatutSante::CERTIFICAT_FOURNI);
            }
        } else {
            $attestation = !empty($payload['attestation']);
            if (!$attestation) {
                throw new \InvalidArgumentException('Cochez l’attestation sur l’honneur (toutes les réponses NON au QS-Sport).');
            }
            $danseur->setAttestationQsSportValide(true);
            $danseur->setDateSignatureQsSport(new \DateTimeImmutable());
            if ($danseur->getStatutSante() !== StatutSante::VALIDE_BUREAU) {
                $danseur->setStatutSante(StatutSante::QS_SPORT_VALIDE);
            }
            // Certificat optionnel même si tout NON
            if (null !== $file) {
                $violations = $validator->validate($file, $fileConstraint);
                if (\count($violations) > 0) {
                    throw new \InvalidArgumentException((string) $violations->get(0)->getMessage());
                }
                $danseur->setCertificatFile($file);
            }
        }
    }

    /**
     * @return list<Danseur>
     */
    private function danseursInscritsPourSaison(Foyer $foyer, string $saison): array
    {
        $byId = [];
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $ins) {
                if ($ins->getSaison() === $saison) {
                    $byId[$danseur->getId()] = $danseur;
                    break;
                }
            }
        }

        return array_values($byId);
    }

    private function handleReglementCheques(
        Request $request,
        Inscription $inscription,
        EchelonnementService $echelonnementService,
        float $resteMax,
    ): void {
        $nombre = (int) $request->request->get('nombre_echeances', 1);
        $emetteur = trim((string) $request->request->get('emetteur', ''));
        $montantRaw = str_replace(',', '.', (string) $request->request->get('montant_echelonne', (string) $resteMax));
        $montant = round((float) $montantRaw, 2);

        if ($emetteur === '') {
            throw new \InvalidArgumentException('Veuillez indiquer le nom de l’émetteur du/des chèque(s).');
        }

        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant à échelonner doit être positif.');
        }

        if ($montant - $resteMax > 0.009) {
            throw new \InvalidArgumentException(sprintf(
                'Le montant à échelonner (%s €) ne peut pas dépasser le reste à payer (%s €).',
                number_format($montant, 2, ',', ' '),
                number_format($resteMax, 2, ',', ' ')
            ));
        }

        $inscription->clearPaiements();
        $paiements = $echelonnementService->generateEcheances($inscription, $nombre, $montant, $emetteur);
        foreach ($paiements as $paiement) {
            $inscription->addPaiement($paiement);
        }
        $inscription->setModePaiement(sprintf('Chèque(s) %dx', $nombre));
    }

    private function handleReglementMixte(
        Request $request,
        Inscription $inscription,
        float $montantTotal,
    ): void {
        $lignes = $request->request->all('lignes') ?? [];
        if (!\is_array($lignes) || $lignes === []) {
            throw new \InvalidArgumentException('Ajoutez au moins une ligne de paiement.');
        }

        $somme = 0.0;
        $paiements = [];

        foreach ($lignes as $index => $ligne) {
            if (!\is_array($ligne)) {
                continue;
            }

            $modeValue = (string) ($ligne['mode'] ?? '');
            $mode = ModePaiement::tryFrom($modeValue);
            if (null === $mode) {
                throw new \InvalidArgumentException(sprintf('Mode de paiement invalide (ligne %d).', $index + 1));
            }

            $montant = round((float) str_replace(',', '.', (string) ($ligne['montant'] ?? '0')), 2);
            if ($montant <= 0) {
                throw new \InvalidArgumentException(sprintf('Montant invalide (ligne %d).', $index + 1));
            }

            $somme = round($somme + $montant, 2);

            $paiement = new Paiement();
            $paiement->setMode($mode);
            $paiement->setMontant($montant);
            $paiement->setStatut(StatutLignePaiement::EN_ATTENTE);
            $paiement->setReference(trim((string) ($ligne['reference'] ?? '')) ?: null);
            $paiement->setEmetteur(trim((string) ($ligne['emetteur'] ?? '')) ?: null);
            $paiements[] = $paiement;
        }

        if (abs($somme - $montantTotal) > 0.009) {
            throw new \InvalidArgumentException(sprintf(
                'La somme des moyens de paiement (%s €) doit être égale au montant total (%s €).',
                number_format($somme, 2, ',', ' '),
                number_format($montantTotal, 2, ',', ' ')
            ));
        }

        $inscription->clearPaiements();
        foreach ($paiements as $paiement) {
            $inscription->addPaiement($paiement);
        }

        $labels = array_unique(array_map(
            static fn (Paiement $p) => $p->getMode()->getLabel(),
            $paiements
        ));
        $inscription->setModePaiement(implode(' + ', $labels));
    }

    /**
     * Répartit le total net foyer au prorata des montants après gratuité 2020.
     */
    private function applyMontantsToInscriptions(Foyer $foyer, string $saison, CotisationDetail $detail): void
    {
        /** @var list<array{inscription: Inscription, poids: float}> $entries */
        $entries = [];
        $poidsTotal = 0.0;

        foreach ($detail->breakdownByDanseur as $block) {
            $danseur = null;
            foreach ($foyer->getDanseurs() as $d) {
                if ($d->getId() === $block->danseurId) {
                    $danseur = $d;
                    break;
                }
            }
            if (null === $danseur) {
                continue;
            }

            foreach ($block->lines as $line) {
                $inscription = null;
                foreach ($danseur->getInscriptions() as $ins) {
                    if ($ins->getSaison() === $saison && $ins->getCours()?->getId() === $line->coursId) {
                        $inscription = $ins;
                        break;
                    }
                }
                if (null === $inscription) {
                    continue;
                }

                $poids = $line->isListeAttente ? 0.0 : $line->montantApresGratuit;
                $entries[] = ['inscription' => $inscription, 'poids' => $poids];
                $poidsTotal += $poids;
            }
        }

        if ($entries === []) {
            return;
        }

        $reste = $detail->total;
        $lastIndex = \count($entries) - 1;

        foreach ($entries as $i => $entry) {
            if ($poidsTotal <= 0.0) {
                $montant = 0.0;
            } elseif ($i === $lastIndex) {
                $montant = round(max(0.0, $reste), 2);
            } else {
                $montant = round($detail->total * ($entry['poids'] / $poidsTotal), 2);
                $reste = round($reste - $montant, 2);
            }
            $entry['inscription']->setMontantTotal($montant);
            $entry['inscription']->refreshStatutPaiement();
        }
    }

    private function findFirstInscriptionSansPaiement(Foyer $foyer, string $saison): ?Inscription
    {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if (($inscription->getMontantTotal() ?? 0.0) <= 0) {
                    continue;
                }
                if ($inscription->getPaiements()->isEmpty()) {
                    return $inscription;
                }
            }
        }

        return null;
    }

    private function findFirstInscriptionSaison(Foyer $foyer, string $saison): ?Inscription
    {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() === $saison) {
                    return $inscription;
                }
            }
        }

        return null;
    }

    /**
     * Première inscription non-brouillon du foyer pour la saison (dossier verrouillé).
     */
    private function findLockedInscriptionFoyer(Foyer $foyer, string $saison): ?Inscription
    {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() === $saison && !$inscription->isEditable()) {
                    return $inscription;
                }
            }
        }

        return null;
    }

    /**
     * @return list<Inscription>
     */
    private function inscriptionsFoyerSaison(Foyer $foyer, string $saison): array
    {
        $list = [];
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() === $saison) {
                    $list[] = $inscription;
                }
            }
        }

        return $list;
    }

    #[Route('/inscription/confirmation/{id}', name: 'app_foyer_inscription_confirmation', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function inscriptionConfirmation(
        Inscription $inscription,
        Request $request,
        EntityManagerInterface $em,
        InscriptionConfirmationMailer $confirmationMailer,
        CotisationCalculatorService $calculator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted(InscriptionTunnelVoter::VIEW_CONFIRMATION, $inscription)) {
            throw $this->createAccessDeniedException('Vous n’avez pas accès à cette confirmation.');
        }

        $danseur = $inscription->getDanseur();
        $foyer = $danseur?->getFoyer();
        if (null === $danseur || null === $foyer) {
            throw $this->createNotFoundException('Inscription introuvable.');
        }

        $saison = $inscription->getSaison();
        $inscriptions = $this->inscriptionsFoyerSaison($foyer, $saison);
        $isOwner = $this->isPrimaryParent($danseur, $user);
        $isBrouillon = $inscription->getStatut() === StatutInscription::BROUILLON;

        if ($request->isMethod('POST') && $isOwner) {
            if (!$this->isCsrfTokenValid('inscription_confirmation' . $inscription->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $action = (string) $request->request->get('_action', 'valider');

            if ($action === 'helloasso_ref') {
                $ref = trim((string) $request->request->get('helloasso_reference', ''));
                foreach ($inscriptions as $ins) {
                    if ($ins->utiliseHelloAsso()) {
                        $ins->setHelloAssoPaymentId($ref !== '' ? $ref : null);
                        foreach ($ins->getPaiements() as $paiement) {
                            if ($paiement->getMode() === ModePaiement::HELLOASSO && $ref !== '') {
                                $paiement->setReference($ref);
                            }
                        }
                    }
                }
                $em->flush();
                $this->addFlash('success', 'Référence HelloAsso enregistrée.');

                return $this->redirectToRoute('app_foyer_inscription_confirmation', ['id' => $inscription->getId()]);
            }

            if ($action === 'valider' && $isBrouillon) {
                foreach ($inscriptions as $ins) {
                    if ($ins->getStatut() === StatutInscription::BROUILLON) {
                        $ins->soumettreAuBureau();
                    }
                }
                $em->flush();

                $responsable = $foyer->getUser();
                if ($responsable) {
                    $confirmationMailer->sendConfirmation($responsable, $foyer, $inscriptions);
                }

                $this->addFlash('success', 'Votre demande d’inscription a bien été transmise au bureau.');

                return $this->redirectToRoute('app_foyer_inscription_confirmation', ['id' => $inscription->getId()]);
            }
        }

        $cotisation = $calculator->calculateForFoyer($foyer, $saison);
        $hasHelloAsso = false;
        $hasVirement = false;
        $hasDepot = false;
        $planReglement = [];
        $totalMontant = 0.0;
        foreach ($inscriptions as $ins) {
            $totalMontant += $ins->getMontantTotal() ?? 0.0;
            if ($ins->utiliseHelloAsso()) {
                $hasHelloAsso = true;
            }
            if ($ins->utiliseVirement()) {
                $hasVirement = true;
            }
            foreach ($ins->getPaiements() as $paiement) {
                $planReglement[] = $paiement;
                $mode = $paiement->getMode();
                if (\in_array($mode, [
                    ModePaiement::CHEQUE,
                    ModePaiement::ANCV,
                    ModePaiement::PASS_SPORT,
                    ModePaiement::ESPECES,
                ], true)) {
                    $hasDepot = true;
                }
            }
        }

        return $this->render('foyer/inscription_confirmation.html.twig', [
            'inscription' => $inscription,
            'inscriptions' => $inscriptions,
            'foyer' => $foyer,
            'responsable' => $foyer->getUser(),
            'saison' => $saison,
            'cotisation' => $cotisation,
            'totalMontant' => round($totalMontant, 2),
            'planReglement' => $planReglement,
            'hasHelloAsso' => $hasHelloAsso,
            'hasVirement' => $hasVirement,
            'hasDepot' => $hasDepot,
            'helloAssoUrl' => $this->helloAssoCampaignUrl,
            'isBrouillon' => $inscription->getStatut() === StatutInscription::BROUILLON,
            'isOwner' => $isOwner,
            'statut' => $inscription->getStatut(),
            'association' => [
                'nom' => 'Studio Danse 430',
                'adresse' => 'Rue Armand Calleau',
                'codePostal' => '85430',
                'ville' => 'Nieul-le-Dolent',
                'email' => 'contact@studiodanse430.fr',
            ],
        ]);
    }

    #[Route('/inscription/{id}/facture', name: 'app_foyer_inscription_facture', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function facture(
        Inscription $inscription,
        CotisationCalculatorService $calculator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $danseur = $inscription->getDanseur();

        if (null === $danseur || !$this->canAccessInscriptionFacture($danseur, $user)) {
            throw $this->createAccessDeniedException('Vous n’avez pas accès à cette facture.');
        }

        $foyer = $danseur->getFoyer();
        if (null === $foyer) {
            throw $this->createNotFoundException('Foyer introuvable pour cette inscription.');
        }

        $saison = $inscription->getSaison();
        $cotisation = $calculator->calculateForFoyer($foyer, $saison);

        $isAcquitte = $inscription->getStatutPaiement() === StatutPaiement::SOLDE;
        $documentTitre = match ($inscription->getStatutPaiement()) {
            StatutPaiement::SOLDE => 'Facture acquittée',
            StatutPaiement::PARTIEL => 'Facture — paiement partiel',
            StatutPaiement::NON_PAYE => 'Attestation d’inscription',
        };

        // Inscriptions du même danseur pour la même saison (détail du dossier)
        $inscriptionsDanseur = [];
        foreach ($danseur->getInscriptions() as $ligne) {
            if ($ligne->getSaison() === $saison) {
                $inscriptionsDanseur[] = $ligne;
            }
        }

        $danseurBreakdown = null;
        foreach ($cotisation->breakdownByDanseur as $block) {
            if ($block->danseurId === $danseur->getId()) {
                $danseurBreakdown = $block;
                break;
            }
        }

        return $this->render('foyer/facture.html.twig', [
            'inscription' => $inscription,
            'danseur' => $danseur,
            'foyer' => $foyer,
            'responsable' => $foyer->getUser(),
            'saison' => $saison,
            'cotisation' => $cotisation,
            'danseurBreakdown' => $danseurBreakdown,
            'inscriptionsDanseur' => $inscriptionsDanseur,
            'documentTitre' => $documentTitre,
            'isAcquitte' => $isAcquitte,
            'numeroFacture' => sprintf(
                'SD430-%s-%04d',
                preg_replace('/\D+/', '', $saison) ?: date('Y'),
                $inscription->getId() ?? 0
            ),
            'association' => [
                'nom' => 'Studio Danse 430',
                'adresse' => 'Rue Armand Calleau',
                'codePostal' => '85430',
                'ville' => 'Nieul-le-Dolent',
                'email' => 'contact@studiodanse430.fr',
                'siret' => 'À compléter',
                'rna' => 'À compléter',
            ],
        ]);
    }

    #[Route('/mon-foyer/desactiver', name: 'app_foyer_desactiver', methods: ['POST'])]
    public function desactiver(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            $user->setIsActif(false);
            $em->flush();
            return $this->redirectToRoute('app_logout');
        }
        return $this->redirectToRoute('app_foyer_index');
    }

    #[Route('/mon-foyer/supprimer', name: 'app_foyer_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            return $this->redirectToRoute('app_home');
        }
        return $this->redirectToRoute('app_foyer_index');
    }

    private function isPrimaryParent(Danseur $danseur, User $user): bool
    {
        return $danseur->getFoyer()?->getUser()?->getId() === $user->getId();
    }

    private function isSecondaryParent(Danseur $danseur, User $user): bool
    {
        $effectif = $danseur->getParent2EmailEffectif();
        if (null === $effectif || '' === trim($effectif)) {
            return false;
        }

        return mb_strtolower(trim($effectif)) === mb_strtolower(trim((string) $user->getEmail()));
    }

    private function canViewDanseur(Danseur $danseur, User $user): bool
    {
        return $this->isPrimaryParent($danseur, $user) || $this->isSecondaryParent($danseur, $user);
    }

    private function canAccessInscriptionFacture(Danseur $danseur, User $user): bool
    {
        if ($this->isGranted('ROLE_BUREAU') || $this->isGranted('ROLE_TRESORIER')) {
            return true;
        }

        return $this->canViewDanseur($danseur, $user);
    }
}
