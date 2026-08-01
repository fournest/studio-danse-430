<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Foyer;
use App\Entity\Danseur;
use App\Entity\Inscription;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use App\Form\FoyerType;
use App\Form\DanseurType;
use App\Repository\CoursRepository;
use App\Repository\DanseurRepository;
use App\Service\CotisationCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-foyer')]
#[IsGranted('ROLE_USER')]
class FoyerController extends AbstractController
{
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
        $allCours = $coursRepository->findAllOrdered();
        $danseurs = $foyer->getDanseurs()->toArray();

        // Sélection initiale : inscriptions saison courante
        $selectionByDanseur = [];
        foreach ($danseurs as $danseur) {
            $selectedIds = [];
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() === $saison && $inscription->getCours()) {
                    $selectedIds[] = $inscription->getCours()->getId();
                }
            }
            $selectionByDanseur[$danseur->getId()] = $selectedIds;
        }

        $action = $request->request->get('_action', 'preview');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('inscription_cours', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $posted = $request->request->all('cours') ?? [];
            $selectionByDanseur = [];
            foreach ($danseurs as $danseur) {
                $rawIds = $posted[(string) $danseur->getId()] ?? [];
                if (!is_array($rawIds)) {
                    $rawIds = [];
                }
                $selectionByDanseur[$danseur->getId()] = array_map('intval', $rawIds);
            }

            if ($action === 'save') {
                $this->persistCourseSelection($em, $foyer, $danseurs, $allCours, $selectionByDanseur, $saison);
                $em->flush();

                $detail = $calculator->calculateForFoyer($foyer, $saison);
                $this->addFlash(
                    'success',
                    sprintf(
                        'Inscriptions enregistrées. Total cotisation saison %s : %s €.',
                        $saison,
                        number_format($detail->total, 2, ',', ' ')
                    )
                );

                return $this->redirectToRoute('app_foyer_index');
            }
        }

        $coursById = [];
        foreach ($allCours as $cours) {
            $coursById[$cours->getId()] = $cours;
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
            foreach ($selectionByDanseur[$danseur->getId()] ?? [] as $coursId) {
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
            ];
        }

        $cotisation = $calculator->calculate($selectionForCalc, $foyer, $saison);

        return $this->render('foyer/inscription_cours.html.twig', [
            'foyer' => $foyer,
            'danseurs' => $danseurs,
            'coursParDanseur' => $coursParDanseur,
            'selectionByDanseur' => $selectionByDanseur,
            'cotisation' => $cotisation,
            'saison' => $saison,
        ]);
    }

    /**
     * @param list<Danseur> $danseurs
     * @param list<\App\Entity\Cours> $allCours
     * @param array<int, list<int>> $selectionByDanseur
     */
    private function persistCourseSelection(
        EntityManagerInterface $em,
        Foyer $foyer,
        array $danseurs,
        array $allCours,
        array $selectionByDanseur,
        string $saison,
    ): void {
        $coursById = [];
        foreach ($allCours as $cours) {
            $coursById[$cours->getId()] = $cours;
        }

        foreach ($danseurs as $danseur) {
            if ($danseur->getFoyer()?->getId() !== $foyer->getId()) {
                continue;
            }

            $wantedIds = array_unique($selectionByDanseur[$danseur->getId()] ?? []);
            $wantedIds = array_values(array_filter(
                $wantedIds,
                static function (int $id) use ($coursById, $danseur): bool {
                    if (!isset($coursById[$id])) {
                        return false;
                    }
                    return $coursById[$id]->isEligibleForDanseur($danseur);
                }
            ));

            // Retirer les inscriptions saison non retenues
            foreach ($danseur->getInscriptions()->toArray() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                $coursId = $inscription->getCours()?->getId();
                if (null === $coursId || !in_array($coursId, $wantedIds, true)) {
                    if ($inscription->getCours()) {
                        $danseur->removeCours($inscription->getCours());
                    }
                    $danseur->removeInscription($inscription);
                    $em->remove($inscription);
                }
            }

            $existingIds = [];
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() === $saison && $inscription->getCours()) {
                    $existingIds[] = $inscription->getCours()->getId();
                }
            }

            foreach ($wantedIds as $coursId) {
                $cours = $coursById[$coursId];
                $danseur->addCours($cours);

                if (in_array($coursId, $existingIds, true)) {
                    continue;
                }

                $inscription = new Inscription();
                $inscription->setDanseur($danseur);
                $inscription->setCours($cours);
                $inscription->setSaison($saison);
                $inscription->setStatutDossier(StatutDossier::EN_ATTENTE);
                $inscription->setStatutPaiement(StatutPaiement::NON_PAYE);
                $em->persist($inscription);
            }
        }
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
}
