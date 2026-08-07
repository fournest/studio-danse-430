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
use App\Form\CoParentContactType;
use App\Form\DeclarerPaiementType;
use App\Form\FoyerType;
use App\Form\DanseurType;
use App\Repository\CoursRepository;
use App\Repository\DanseurRepository;
use App\Repository\InscriptionRepository;
use App\Security\Voter\InscriptionTunnelVoter;
use App\Service\CoParentMailerService;
use App\Service\CotisationCalculatorService;
use App\Service\DeclarerPaiementFoyerService;
use App\Service\EchelonnementService;
use App\Service\FoyerFusionService;
use App\Service\InscriptionAutofillService;
use App\Service\InscriptionConfirmationMailer;
use App\Service\QsSportQuestionnaire;
use App\Service\VirementLibelleService;
use App\Repository\DemandeFusionFoyerRepository;
use App\Repository\FoyerRepository;
use App\Repository\UserRepository;
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
    public function index(
        DanseurRepository $danseurRepository,
        UserRepository $userRepository,
        CotisationCalculatorService $cotisationService,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        $danseursProprietaire = $foyer ? $foyer->getDanseurs()->toArray() : [];
        $danseursRattaches = $danseurRepository->findAccessibleByParent2Email((string) $user->getEmail());

        $isParentPrincipal = null !== $foyer && $foyer->getUser()?->getId() === $user->getId();
        $isCoparent = $danseursRattaches !== [];

        // Coparent pur : uniquement les enfants rattachés. Parent principal : foyer + éventuels rattachements externes.
        if ($isParentPrincipal) {
            $byId = [];
            foreach (array_merge($danseursProprietaire, $danseursRattaches) as $danseur) {
                $byId[$danseur->getId()] = $danseur;
            }
            $allDanseurs = array_values($byId);
        } else {
            $allDanseurs = $danseursRattaches;
        }

        if (!$isParentPrincipal && empty($allDanseurs)) {
            return $this->redirectToRoute('app_foyer_new');
        }

        $saison = CotisationCalculatorService::SAISON_COURANTE;
        $cotisation = null;
        $paiementInscriptionId = null;
        $reglementSoumis = false;
        $reglementSolde = false;
        $financierFoyer = null;

        // Recalcule le total foyer ; persiste les parts tant qu’aucun règlement n’a commencé.
        // Uniquement pour le parent principal (jamais pour un coparent en lecture seule).
        if ($isParentPrincipal && $foyer && !$foyer->getDanseurs()->isEmpty()) {
            $cotisation = $cotisationService->calculerTotalFoyer($foyer, $saison);
            $reglementSoumis = $this->foyerHasPaiementsSaison($foyer, $saison);
            if (!$reglementSoumis) {
                $this->applyMontantsToInscriptions($foyer, $saison, $cotisation);
                $em->flush();
            } else {
                foreach ($foyer->getDanseurs() as $danseur) {
                    foreach ($danseur->getInscriptions() as $inscription) {
                        if ($inscription->getSaison() === $saison && $inscription->hasPlanReglement()) {
                            $inscription->refreshStatutPaiement();
                        }
                    }
                }
                $em->flush();
                $reglementSolde = $this->foyerReglementSolde($foyer, $saison);
            }

            $financierFoyer = [
                'total_du' => $foyer->getTotalDu($saison),
                'total_declare' => $foyer->getTotalDeclare($saison),
                'total_encaisse' => $foyer->getTotalEncaisse($saison),
                'reste_a_payer' => $foyer->getResteAPayer($saison),
                'reste_apres_declaration' => $foyer->getResteAPayerApresDeclaration($saison),
                'paiements_declares' => $foyer->getPaiementsDeclares($saison),
            ];

            $sansPaiement = $this->findFirstInscriptionSansPaiement($foyer, $saison);
            $paiementInscriptionId = $sansPaiement?->getId()
                ?? $this->findFirstInscriptionAvecMontant($foyer, $saison)?->getId()
                ?? $this->findFirstInscriptionSaison($foyer, $saison)?->getId();
        }

        $parent2CompteCree = [];
        $danseursAvecCours = 0;
        $danseursSanteOk = 0;
        $nbInscriptions = 0;
        $nbReglementsOk = 0;
        $premiereInscriptionId = null;
        $canEditByDanseurId = [];

        foreach ($allDanseurs as $danseur) {
            $canEditByDanseurId[$danseur->getId()] = $this->isPrimaryParent($danseur, $user);

            $email = trim((string) $danseur->getParent2EmailEffectif());
            if ($email !== '') {
                $existing = $userRepository->createQueryBuilder('u')
                    ->andWhere('LOWER(u.email) = :email')
                    ->setParameter('email', mb_strtolower($email))
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
                $parent2CompteCree[$danseur->getId()] = null !== $existing;
            }

            if ($danseur->getInscriptions()->isEmpty()) {
                continue;
            }

            ++$danseursAvecCours;
            if ($danseur->hasJustificatifSanteComplet()) {
                ++$danseursSanteOk;
            }

            foreach ($danseur->getInscriptions() as $inscription) {
                ++$nbInscriptions;
                if (null === $premiereInscriptionId && $canEditByDanseurId[$danseur->getId()]) {
                    $premiereInscriptionId = $inscription->getId();
                }
                $paiementOk = $inscription->getStatutPaiement() === StatutPaiement::SOLDE
                    || $inscription->hasPlanReglement()
                    || ($inscription->getMontantTotal() ?? 0.0) <= 0.0;
                if ($paiementOk) {
                    ++$nbReglementsOk;
                }
            }
        }

        $step1Done = \count($danseursProprietaire) > 0;
        $step2Done = $isParentPrincipal && $danseursAvecCours > 0;
        $step3Done = $step2Done && $danseursSanteOk >= $danseursAvecCours;
        $step4Done = $reglementSoumis
            || ($step2Done && $nbInscriptions > 0 && $nbReglementsOk >= $nbInscriptions);
        $stepCourante = match (true) {
            !$step1Done => 1,
            !$step2Done => 2,
            !$step3Done => 3,
            !$step4Done => 4,
            default => 4,
        };

        return $this->render('foyer/index.html.twig', [
            'foyer' => $isParentPrincipal ? $foyer : null,
            'danseurs' => $allDanseurs,
            'isParentPrincipal' => $isParentPrincipal,
            'isCoparent' => $isCoparent,
            'isFoyerOwner' => $isParentPrincipal, // alias rétrocompat template
            'ownedFoyerId' => $isParentPrincipal ? $foyer?->getId() : null,
            'canEditByDanseurId' => $canEditByDanseurId,
            'parent2_compte_cree' => $parent2CompteCree,
            'step1_done' => $step1Done,
            'step2_done' => $step2Done,
            'step3_done' => $step3Done,
            'step4_done' => $step4Done,
            'step_courante' => $stepCourante,
            'premiere_inscription_id' => $premiereInscriptionId,
            'paiement_inscription_id' => $paiementInscriptionId ?? $premiereInscriptionId,
            'cotisation' => $cotisation,
            'reglement_soumis' => $reglementSoumis,
            'reglement_solde' => $reglementSolde,
            'saison' => $saison,
            'financier_foyer' => $financierFoyer,
            'declarer_paiement_form' => $isParentPrincipal && $foyer && $reglementSoumis
                ? $this->createForm(DeclarerPaiementType::class)->createView()
                : null,
        ]);
    }

    #[Route('/declarer-paiement', name: 'app_foyer_declarer_paiement', methods: ['POST'])]
    public function declarerPaiement(
        Request $request,
        DeclarerPaiementFoyerService $declarerPaiementService,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        if (null === $foyer || $foyer->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $saison = CotisationCalculatorService::SAISON_COURANTE;
        if (!$this->foyerHasPaiementsSaison($foyer, $saison)) {
            $this->addFlash('warning', 'Aucun plan de règlement enregistré pour cette saison.');

            return $this->redirectToRoute('app_foyer_index');
        }

        $form = $this->createForm(DeclarerPaiementType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire de déclaration de paiement est invalide.');

            return $this->redirectToRoute('app_foyer_index');
        }

        /** @var ModePaiement $mode */
        $mode = $form->get('mode')->getData();
        $montant = (float) $form->get('montant')->getData();
        $reference = $form->get('reference')->getData();

        try {
            $paiement = $declarerPaiementService->declarer($foyer, $saison, $mode, $montant, $reference);
            $em->flush();
            $this->addFlash(
                'success',
                sprintf(
                    'Paiement de %s € déclaré le %s — En cours de confirmation par la trésorerie.',
                    number_format($paiement->getMontant(), 2, ',', ' '),
                    $paiement->getDateDeclaration()?->format('d/m/Y') ?? date('d/m/Y')
                )
            );
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_foyer_index');
    }

    #[Route('/mes-coordonnees', name: 'app_foyer_coparent_contact', methods: ['GET', 'POST'])]
    public function coparentContact(
        Request $request,
        EntityManagerInterface $em,
        DanseurRepository $danseurRepository,
        UserRepository $userRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $danseursRattaches = $danseurRepository->findAccessibleByParent2Email((string) $user->getEmail());

        if ($danseursRattaches === []) {
            $this->addFlash('warning', 'Aucun enfant ne vous est rattaché en tant que co-parent.');

            return $this->redirectToRoute('app_foyer_index');
        }

        $form = $this->createForm(CoParentContactType::class, null, [
            'email' => (string) $user->getEmail(),
            'telephone' => (string) ($user->getTelephone() ?: ($danseursRattaches[0]->getParent2TelephoneEffectif() ?? '')),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newEmail = mb_strtolower(trim((string) $form->get('email')->getData()));
            $newPhone = trim((string) $form->get('telephone')->getData());
            $oldEmail = mb_strtolower(trim((string) $user->getEmail()));

            if ($newEmail !== $oldEmail) {
                $conflict = $userRepository->createQueryBuilder('u')
                    ->andWhere('LOWER(u.email) = :email')
                    ->andWhere('u.id != :id')
                    ->setParameter('email', $newEmail)
                    ->setParameter('id', $user->getId())
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
                if (null !== $conflict) {
                    $this->addFlash('error', 'Cet e-mail est déjà utilisé par un autre compte.');

                    return $this->redirectToRoute('app_foyer_coparent_contact');
                }
            }

            $user->setEmail($newEmail);
            $user->setTelephone($newPhone);

            foreach ($danseursRattaches as $danseur) {
                $danseur->setParent2Email($newEmail);
                $danseur->setParent2Telephone($newPhone);
            }

            $em->flush();
            $this->addFlash('success', 'Vos coordonnées de co-parent ont été mises à jour.');

            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/coparent_contact.html.twig', [
            'form' => $form->createView(),
            'danseurs' => $danseursRattaches,
        ]);
    }

    #[Route('/configuration', name: 'app_foyer_new', methods: ['GET', 'POST'])]
    #[Route('/modifier', name: 'app_foyer_edit_dossier', methods: ['GET', 'POST'])]
    public function configure(
        Request $request,
        EntityManagerInterface $em,
        FoyerRepository $foyerRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        // Un co-parent sans foyer titulaire ne crée pas de dossier familial ici.
        if (null === $foyer && $request->attributes->get('_route') === 'app_foyer_edit_dossier') {
            return $this->redirectToRoute('app_foyer_coparent_contact');
        }

        $isEdit = $foyer !== null;

        if (!$isEdit) {
            $foyer = new Foyer();
        }

        $form = $this->createForm(FoyerType::class, $foyer, [
            'telephone' => (string) $user->getTelephone(),
        ]);
        $form->handleRequest($request);

        $foyerTrouve = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTelephone(trim((string) $form->get('telephone')->getData()));

            if (!$isEdit) {
                $foyer->setUser($user);
                $user->setFoyer($foyer);
                $em->persist($foyer);
            }

            $em->flush();

            $foyerTrouve = $foyerRepository->findOtherFoyerByAdresse(
                (string) $foyer->getAdresse(),
                (string) $foyer->getCodePostal(),
                $foyer,
            );

            if (null !== $foyerTrouve) {
                return $this->render('foyer/configuration.html.twig', [
                    'form' => $form->createView(),
                    'is_edit' => true,
                    'title' => 'Adresse déjà utilisée',
                    'submit_label' => 'Enregistrer les modifications',
                    'foyerTrouve' => $foyerTrouve,
                    'show_fusion_choice' => true,
                ]);
            }

            if ($isEdit) {
                $this->addFlash('success', 'Les informations de votre dossier familial ont bien été mises à jour.');
            } else {
                $this->addFlash('success', 'Votre dossier familial a bien été configuré ! Vous pouvez maintenant ajouter vos danseurs.');
            }

            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/configuration.html.twig', [
            'form' => $form->createView(),
            'is_edit' => $isEdit,
            'title' => $isEdit
                ? 'Modification du dossier familial'
                : 'Configuration du dossier familial',
            'submit_label' => $isEdit
                ? 'Enregistrer les modifications'
                : 'Enregistrer et continuer',
            'foyerTrouve' => null,
            'show_fusion_choice' => false,
        ]);
    }

    #[Route('/demande-fusion/{idTargetFoyer}', name: 'app_foyer_demande_fusion', methods: ['POST'], requirements: ['idTargetFoyer' => '\d+'])]
    public function demandeFusion(
        int $idTargetFoyer,
        Request $request,
        FoyerRepository $foyerRepository,
        FoyerFusionService $fusionService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $foyerSource = $user->getFoyer();

        if (null === $foyerSource) {
            throw $this->createNotFoundException('Aucun foyer associé à votre compte.');
        }

        if (!$this->isCsrfTokenValid('demande_fusion_' . $idTargetFoyer, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($foyerSource->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Seul le titulaire du foyer peut demander une fusion.');
        }

        $foyerTarget = $foyerRepository->find($idTargetFoyer);
        if (null === $foyerTarget) {
            throw $this->createNotFoundException('Foyer destinataire introuvable.');
        }

        if ($foyerTarget->getId() === $foyerSource->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas fusionner un foyer avec lui-même.');

            return $this->redirectToRoute('app_foyer_index');
        }

        try {
            $fusionService->createAndSendDemande($foyerSource, $foyerTarget, $user);
            $this->addFlash('success', 'Une demande de confirmation a été envoyée à votre conjointe.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Impossible d’envoyer la demande de fusion : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_foyer_index');
    }

    #[Route('/valider-fusion/{token}', name: 'app_foyer_valider_fusion', methods: ['GET'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function validerFusion(
        string $token,
        FoyerFusionService $fusionService,
        DemandeFusionFoyerRepository $demandeRepository,
    ): Response {
        $demande = $demandeRepository->findOneBy(['token' => $token]);

        if (null === $demande) {
            $this->addFlash('danger', 'Lien de fusion invalide.');

            return $this->redirectToRoute('app_home');
        }

        /** @var User $current */
        $current = $this->getUser();

        $result = $fusionService->accepterFusion($demande, $current);
        $this->addFlash($result['ok'] ? 'success' : 'danger', $result['message']);

        return $this->redirectToRoute($result['ok'] ? 'app_foyer_index' : 'app_home');
    }

    #[Route('/ajouter-un-danseur', name: 'app_foyer_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        CoParentMailerService $coParentMailer,
    ): Response {
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

            if ($this->shouldSendParent2Invitation($danseur, false)) {
                if ($coParentMailer->sendInvitation($danseur)) {
                    $em->flush();
                    $this->addFlash('success', 'Une invitation a été envoyée au second parent.');
                }
            }

            $this->addFlash('success', $danseur->getPrenom() . ' a bien été ajouté(e) au foyer !');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter un membre au foyer',
            'show_parent2_section' => true,
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
    public function edit(
        Danseur $danseur,
        Request $request,
        EntityManagerInterface $em,
        CoParentMailerService $coParentMailer,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isPrimaryParent($danseur, $user)) {
            $this->addFlash('warning', 'Vous êtes en mode lecture seule sur ce profil.');
            return $this->redirectToRoute('app_foyer_show', ['id' => $danseur->getId()]);
        }

        $previousEmail = $danseur->getParent2Email();
        $allowResend = $danseur->getParent2InvitedAt() !== null;

        $form = $this->createForm(DanseurType::class, $danseur, [
            'allow_resend_invite' => $allowResend,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailChanged = mb_strtolower(trim((string) $previousEmail))
                !== mb_strtolower(trim((string) $danseur->getParent2Email()));

            if ($emailChanged) {
                $danseur->setParent2InvitedAt(null);
            }

            $resend = $allowResend && (bool) $form->get('renvoyerInvitation')->getData();

            $em->flush();

            if ($this->shouldSendParent2Invitation($danseur, $resend || $emailChanged)) {
                if ($coParentMailer->sendInvitation($danseur)) {
                    $em->flush();
                    $this->addFlash('success', 'Une invitation a été envoyée au second parent.');
                }
            }

            $this->addFlash('success', 'Le profil de ' . $danseur->getPrenom() . ' a été mis à jour.');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le profil de ' . $danseur->getPrenom(),
            'show_parent2_section' => true,
        ]);
    }

    private function shouldSendParent2Invitation(Danseur $danseur, bool $forceResend): bool
    {
        $email = trim((string) $danseur->getParent2Email());
        if ($email === '') {
            return false;
        }

        if ($forceResend) {
            return true;
        }

        return null === $danseur->getParent2InvitedAt();
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
                $allowAgeOverride = $this->isGranted('ROLE_BUREAU');

                if (!$allowAgeOverride && $this->selectionContainsIncompatibleCours(
                    $danseurs,
                    $allCours,
                    $selectionByDanseur,
                    $attenteByDanseur,
                )) {
                    $this->addFlash(
                        'error',
                        'Certains cours sélectionnés ne correspondent pas à la tranche d’âge de l’élève.'
                    );
                } else {
                    $forcedWaitlist = $this->persistCourseSelection(
                        $em,
                        $inscriptionRepository,
                        $foyer,
                        $danseurs,
                        $allCours,
                        $selectionByDanseur,
                        $attenteByDanseur,
                        $saison,
                        $allowAgeOverride,
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

                    $detail = $calculator->calculerTotalFoyer($foyer, $saison);
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
                            'Inscriptions enregistrées. Cotisation cours : %s € — total foyer (avec boutique) : %s €.',
                            number_format($detail->total, 2, ',', ' '),
                            number_format($detail->grandTotal, 2, ',', ' ')
                        )
                    );

                    $firstInscription = $this->findFirstInscriptionSaison($foyer, $saison);
                    if (null !== $firstInscription) {
                        return $this->redirectToRoute('app_foyer_inscription_paiement', ['id' => $firstInscription->getId()]);
                    }

                    return $this->redirectToRoute('app_foyer_index');
                }
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
                if ($cours->isCompatibleAvecDanseur($danseur)) {
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
                if (!$cours->isCompatibleAvecDanseur($danseur)) {
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
     */
    private function selectionContainsIncompatibleCours(
        array $danseurs,
        array $allCours,
        array $selectionByDanseur,
        array $attenteByDanseur,
    ): bool {
        $coursById = [];
        foreach ($allCours as $cours) {
            $coursById[$cours->getId()] = $cours;
        }

        foreach ($danseurs as $danseur) {
            $ids = array_values(array_unique(array_merge(
                $selectionByDanseur[$danseur->getId()] ?? [],
                $attenteByDanseur[$danseur->getId()] ?? [],
            )));

            foreach ($ids as $coursId) {
                if (!isset($coursById[$coursId])) {
                    continue;
                }
                if (!$coursById[$coursId]->isCompatibleAvecDanseur($danseur)) {
                    return true;
                }
            }
        }

        return false;
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
        bool $allowAgeOverride = false,
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

            $filterEligible = static function (array $ids) use ($coursById, $danseur, $allowAgeOverride): array {
                return array_values(array_filter(
                    $ids,
                    static function (int $id) use ($coursById, $danseur, $allowAgeOverride): bool {
                        if (!isset($coursById[$id])) {
                            return false;
                        }

                        if ($allowAgeOverride) {
                            return true;
                        }

                        return $coursById[$id]->isCompatibleAvecDanseur($danseur);
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

            $filterEligible = static function (array $ids) use ($coursById, $danseur, $allowAgeOverride): array {
                return array_values(array_filter(
                    $ids,
                    static function (int $id) use ($coursById, $danseur, $allowAgeOverride): bool {
                        if (!isset($coursById[$id])) {
                            return false;
                        }

                        if ($allowAgeOverride) {
                            return true;
                        }

                        return $coursById[$id]->isCompatibleAvecDanseur($danseur);
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

    #[Route('/inscription/{id}/boutique', name: 'app_foyer_inscription_boutique', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function inscriptionBoutique(Inscription $inscription): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $danseur = $inscription->getDanseur();

        if (null === $danseur || !$this->isPrimaryParent($danseur, $user)) {
            throw $this->createAccessDeniedException('Vous n’avez pas accès à cette étape.');
        }

        $this->addFlash(
            'info',
            'La boutique et les locations de costumes ont leurs propres tunnels de paiement. Le règlement foyer concerne uniquement les cours.'
        );

        return $this->redirectToRoute('app_foyer_inscription_paiement', ['id' => $inscription->getId()]);
    }

    #[Route('/confirmation', name: 'app_foyer_confirmation', methods: ['GET'])]
    public function confirmationAlias(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();
        if (!$foyer) {
            return $this->redirectToRoute('app_foyer_index');
        }

        $saison = CotisationCalculatorService::SAISON_COURANTE;
        $inscription = $this->findFirstInscriptionSaison($foyer, $saison);
        if (!$inscription) {
            $this->addFlash('warning', 'Aucune inscription à confirmer pour la saison en cours.');

            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->redirectToRoute('app_foyer_inscription_confirmation', ['id' => $inscription->getId()]);
    }

    #[Route('/inscription/{id}/paiement', name: 'app_foyer_inscription_paiement', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function inscriptionPaiement(
        Inscription $inscription,
        Request $request,
        EntityManagerInterface $em,
        EchelonnementService $echelonnementService,
        CotisationCalculatorService $cotisationService,
        VirementLibelleService $virementLibelleService,
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

        $foyer = $danseur->getFoyer();
        $saison = $inscription->getSaison() ?: CotisationCalculatorService::SAISON_COURANTE;
        $cotisation = null;
        $libelleVirement = null;

        if ($foyer) {
            $cotisation = $cotisationService->calculerTotalFoyer($foyer, $saison);
            $hadRef = (bool) $foyer->getReferenceVirement();
            $libelleVirement = $virementLibelleService->ensureReference($foyer, $saison);

            if (!$this->foyerHasPaiementsSaison($foyer, $saison)) {
                $this->applyMontantsToInscriptions($foyer, $saison, $cotisation);
                // Règlement unique : concentre le total foyer sur l’inscription courante.
                $this->concentrerMontantFoyerSurInscription($foyer, $saison, $inscription, $cotisation);
                $em->flush();
            } else {
                if (!$hadRef) {
                    $em->flush();
                }
                if (($inscription->getMontantTotal() ?? 0.0) <= 0 && $cotisation->grandTotal > 0) {
                    $cible = $this->findFirstInscriptionSansPaiement($foyer, $saison)
                        ?? $this->findFirstInscriptionAvecMontant($foyer, $saison);
                    if (null !== $cible && $cible->getId() !== $inscription->getId()) {
                        return $this->redirectToRoute('app_foyer_inscription_paiement', ['id' => $cible->getId()]);
                    }
                }
            }
        }

        $montantTotal = $cotisation?->grandTotal > 0
            ? $cotisation->grandTotal
            : ($inscription->getMontantTotal() ?? 0.0);
        $resteAPayer = $foyer
            ? $this->calculerResteAPayerFoyer($foyer, $saison)
            : $inscription->getResteAPayer();

        // Si l’inscription porte déjà le total foyer (après concentration), utilise ses valeurs.
        if (($inscription->getMontantTotal() ?? 0.0) >= ($cotisation?->grandTotal ?? 0.0) - 0.01
            && ($cotisation?->grandTotal ?? 0.0) > 0) {
            $montantTotal = $inscription->getMontantTotal() ?? $montantTotal;
            $resteAPayer = $inscription->getResteAPayer();
        }

        if ($resteAPayer <= 0 && $montantTotal <= 0) {
            $this->addFlash('warning', 'Aucun montant à régler pour cette inscription.');
            return $this->redirectToRoute('app_foyer_index');
        }

        if ($resteAPayer <= 0 && $montantTotal > 0) {
            $this->addFlash('success', 'Le règlement de votre cotisation foyer est déjà enregistré.');
            return $this->redirectToRoute('app_foyer_index');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('inscription_paiement' . $inscription->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            try {
                $this->handleReglementEchelonne(
                    $request,
                    $inscription,
                    $foyer,
                    $echelonnementService,
                    $virementLibelleService,
                    $resteAPayer,
                );

                // modePaiement + échéances Paiement déjà posés ; s’assurer du montant exact.
                if ($foyer) {
                    $cotisation = $cotisationService->calculerTotalFoyer($foyer, $saison);
                    $this->concentrerMontantFoyerSurInscription($foyer, $saison, $inscription, $cotisation);
                }

                $inscription->refreshStatutPaiement();

                if ($foyer && $inscription->getResteAPayer() <= 0) {
                    $this->marquerInscriptionsFoyerSoldees($foyer, $saison, $inscription);
                }

                $em->flush();

                $this->addFlash('success', 'Vos moyens de paiement ont bien été enregistrés. Le bureau procédera à l’encaissement.');

                return $this->redirectToRoute('app_foyer_inscription_sante', ['id' => $inscription->getId()]);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $datesParOption = [];
        $montantsParOption = [];
        foreach ([1, 3, 10] as $n) {
            $datesParOption[$n] = array_map(
                static fn (\DateTimeImmutable $d) => $d->format('d/m/Y'),
                $echelonnementService->genererDatesEncaissement($inscription->getSaison(), $n)
            );
            $montantsParOption[$n] = $echelonnementService->repartirMontants($resteAPayer, $n);
        }

        return $this->render('foyer/inscription_paiement.html.twig', [
            'inscription' => $inscription,
            'danseur' => $danseur,
            'montantTotal' => $montantTotal,
            'resteAPayer' => $resteAPayer,
            'cotisation' => $cotisation,
            'libelleVirement' => $libelleVirement,
            'datesParOption' => $datesParOption,
            'montantsParOption' => $montantsParOption,
            'modesDeduction' => ModePaiement::modesDeductionFoyer(),
            'paiementsExistants' => $inscription->getPaiements(),
        ]);
    }

    #[Route('/inscription/{id}/sante', name: 'app_foyer_inscription_sante', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function inscriptionSante(
        Inscription $inscription,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        InscriptionAutofillService $autofill,
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
                $autofill->syncSanteFoyer($foyer, $saison);
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

    private function handleReglementEchelonne(
        Request $request,
        Inscription $inscription,
        ?Foyer $foyer,
        EchelonnementService $echelonnementService,
        VirementLibelleService $virementLibelleService,
        float $resteMax,
    ): void {
        [$deductions, $sommeDeductions] = $this->parseLignesDeduction($request);

        if ($sommeDeductions - $resteMax > 0.009) {
            throw new \InvalidArgumentException(sprintf(
                'Le total des aides (%s €) ne peut pas dépasser la cotisation (%s €).',
                number_format($sommeDeductions, 2, ',', ' '),
                number_format($resteMax, 2, ',', ' ')
            ));
        }

        $montantAEchelonner = round(max(0.0, $resteMax - $sommeDeductions), 2);

        $inscription->clearPaiements();
        foreach ($deductions as $paiement) {
            $inscription->addPaiement($paiement);
        }

        $labels = array_map(
            static fn (Paiement $p) => $p->getMode()->getLabel(),
            $deductions
        );

        if ($montantAEchelonner > 0.009) {
            $nombre = (int) $request->request->get('nombre_echeances', 1);
            $modeValue = (string) $request->request->get('mode_echeance', ModePaiement::CHEQUE->value);
            $mode = ModePaiement::tryFrom($modeValue);
            if (null === $mode || !\in_array($mode, ModePaiement::modesEchelonnes(), true)) {
                throw new \InvalidArgumentException('Choisissez Chèque(s) ou Virement(s) pour le solde à échelonner.');
            }

            $emetteur = null;
            $libelleVirement = null;

            if ($mode === ModePaiement::CHEQUE) {
                $emetteur = trim((string) $request->request->get('emetteur', ''));
                if ($emetteur === '') {
                    throw new \InvalidArgumentException('Veuillez indiquer le nom de l’émetteur du/des chèque(s).');
                }
            } else {
                if (null === $foyer) {
                    throw new \InvalidArgumentException('Foyer introuvable pour générer le libellé de virement.');
                }
                $libelleVirement = $virementLibelleService->ensureReference($foyer, $inscription->getSaison());
            }

            $echeances = $echelonnementService->generateEcheances(
                $inscription,
                $nombre,
                $montantAEchelonner,
                $emetteur,
                $mode,
                $libelleVirement,
            );
            foreach ($echeances as $paiement) {
                $inscription->addPaiement($paiement);
            }

            $labels[] = sprintf(
                '%s %dx',
                $mode === ModePaiement::VIREMENT ? 'Virement(s)' : 'Chèque(s)',
                $nombre
            );
        } elseif ($deductions === []) {
            throw new \InvalidArgumentException('Indiquez au moins une aide ou un solde à échelonner.');
        }

        $totalCouvert = round($sommeDeductions + $montantAEchelonner, 2);
        if (abs($totalCouvert - $resteMax) > 0.009) {
            throw new \InvalidArgumentException(sprintf(
                'Le règlement (%s €) doit couvrir exactement la cotisation (%s €).',
                number_format($totalCouvert, 2, ',', ' '),
                number_format($resteMax, 2, ',', ' ')
            ));
        }

        $inscription->setModePaiement(implode(' + ', array_unique($labels)));
    }

    /**
     * @return array{0: list<Paiement>, 1: float}
     */
    private function parseLignesDeduction(Request $request): array
    {
        $lignes = $request->request->all('deductions') ?? [];
        if (!\is_array($lignes)) {
            return [[], 0.0];
        }

        $paiements = [];
        $somme = 0.0;
        $modesAutorises = ModePaiement::modesDeductionFoyer();

        foreach ($lignes as $index => $ligne) {
            if (!\is_array($ligne)) {
                continue;
            }

            $montantRaw = trim((string) ($ligne['montant'] ?? ''));
            if ($montantRaw === '') {
                continue;
            }

            $modeValue = (string) ($ligne['mode'] ?? '');
            $mode = ModePaiement::tryFrom($modeValue);
            if (null === $mode || !\in_array($mode, $modesAutorises, true)) {
                throw new \InvalidArgumentException(sprintf('Mode d’aide invalide (ligne %d).', $index + 1));
            }

            $montant = round((float) str_replace(',', '.', $montantRaw), 2);
            if ($montant <= 0) {
                throw new \InvalidArgumentException(sprintf('Montant d’aide invalide (ligne %d).', $index + 1));
            }

            $somme = round($somme + $montant, 2);

            $paiement = new Paiement();
            $paiement->setMode($mode);
            $paiement->setMontant($montant);
            $paiement->setStatut(StatutLignePaiement::EN_ATTENTE_REGLEMENT);
            $paiement->setReference(trim((string) ($ligne['reference'] ?? '')) ?: null);
            $paiement->setRemarques('Aide / autre règlement soustrait du solde à échelonner');
            $paiements[] = $paiement;
        }

        return [$paiements, $somme];
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

    private function foyerHasPaiementsSaison(Foyer $foyer, string $saison): bool
    {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if (!$inscription->getPaiements()->isEmpty()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * True si tous les montants du foyer pour la saison sont effectivement encaissés / soldés.
     */
    private function foyerReglementSolde(Foyer $foyer, string $saison): bool
    {
        $hasPayable = false;
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                $total = $inscription->getMontantTotal() ?? 0.0;
                if ($total <= 0.001 && $inscription->getPaiements()->isEmpty()) {
                    continue;
                }
                $hasPayable = true;
                if ($inscription->getStatutPaiement() !== StatutPaiement::SOLDE) {
                    return false;
                }
            }
        }

        return $hasPayable;
    }

    /**
     * Place le total cotisation foyer sur une inscription (règlement unique).
     */
    private function concentrerMontantFoyerSurInscription(
        Foyer $foyer,
        string $saison,
        Inscription $cible,
        CotisationDetail $detail,
    ): void {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if ($inscription->getId() === $cible->getId()) {
                    $inscription->setMontantTotal($detail->grandTotal);
                } else {
                    $inscription->setMontantTotal(0);
                }
                $inscription->refreshStatutPaiement();
            }
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

    private function findFirstInscriptionAvecMontant(Foyer $foyer, string $saison): ?Inscription
    {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if (($inscription->getMontantTotal() ?? 0.0) > 0) {
                    return $inscription;
                }
            }
        }

        return null;
    }

    private function calculerResteAPayerFoyer(Foyer $foyer, string $saison): float
    {
        $reste = 0.0;
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                $reste += $inscription->getResteAPayer();
            }
        }

        return round($reste, 2);
    }

    /**
     * Après règlement du total foyer sur une inscription, solde les autres lignes de la saison.
     */
    private function marquerInscriptionsFoyerSoldees(Foyer $foyer, string $saison, Inscription $payee): void
    {
        foreach ($foyer->getDanseurs() as $danseur) {
            foreach ($danseur->getInscriptions() as $inscription) {
                if ($inscription->getSaison() !== $saison) {
                    continue;
                }
                if ($inscription->getId() === $payee->getId()) {
                    continue;
                }
                // Conserve le montant déjà encaissé ; le reste du foyer a été réglé sur $payee.
                $inscription->setMontantTotal($inscription->getMontantRegle());
                if (($inscription->getMontantTotal() ?? 0.0) <= 0.001) {
                    $inscription->setMontantTotal(0);
                }
                $inscription->setStatutPaiement(StatutPaiement::SOLDE);
            }
        }
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
        InscriptionAutofillService $autofill,
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
                $autofill->finaliserSoumissionFoyer($foyer, $saison);
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

    #[Route('/facture-ce', name: 'app_foyer_facture_ce', methods: ['GET'])]
    public function factureCe(CotisationCalculatorService $calculator): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $foyer = $currentUser->getFoyer();

        if (null === $foyer) {
            throw $this->createNotFoundException('Aucun foyer associé à votre compte.');
        }

        $isTitulaire = $foyer->getUser()?->getId() === $currentUser->getId();
        if (!$isTitulaire && !$this->isGranted('ROLE_BUREAU')) {
            throw $this->createAccessDeniedException(
                'Seuls le titulaire du foyer ou le bureau peuvent télécharger cette attestation.'
            );
        }

        $saison = CotisationCalculatorService::SAISON_COURANTE;
        $cotisation = $calculator->calculateForFoyer($foyer, $saison);

        // Parts nettes proportionnelles (même logique que applyMontantsToInscriptions).
        $payingSubtotal = 0.0;
        foreach ($cotisation->breakdownByDanseur as $block) {
            foreach ($block->lines as $line) {
                if (!$line->isListeAttente) {
                    $payingSubtotal += $line->montantApresGratuit;
                }
            }
        }

        $pagesDanseurs = [];
        $lignesRecap = [];
        $resteNet = $cotisation->total;

        $blocks = $cotisation->breakdownByDanseur;
        $flatPaying = [];
        foreach ($blocks as $bi => $block) {
            foreach ($block->lines as $li => $line) {
                if (!$line->isListeAttente && $line->montantApresGratuit > 0) {
                    $flatPaying[] = [$bi, $li];
                }
            }
        }
        $lastPayingKey = $flatPaying !== [] ? end($flatPaying) : null;

        foreach ($blocks as $bi => $block) {
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

            $coursPages = [];
            foreach ($block->lines as $li => $line) {
                if ($line->isListeAttente) {
                    continue;
                }

                $inscription = null;
                $cours = null;
                foreach ($danseur->getInscriptions() as $ins) {
                    if ($ins->getSaison() === $saison
                        && $ins->getCours()?->getId() === $line->coursId
                        && $ins->getStatut() !== StatutInscription::ANNULE
                    ) {
                        $inscription = $ins;
                        $cours = $ins->getCours();
                        break;
                    }
                }

                if ($line->isGratuit2020 || $line->montantApresGratuit <= 0) {
                    $montantNet = 0.0;
                } elseif ($payingSubtotal <= 0) {
                    $montantNet = 0.0;
                } elseif ($lastPayingKey !== null && $lastPayingKey[0] === $bi && $lastPayingKey[1] === $li) {
                    $montantNet = round(max(0.0, $resteNet), 2);
                } else {
                    $montantNet = round($cotisation->total * ($line->montantApresGratuit / $payingSubtotal), 2);
                    $resteNet = round($resteNet - $montantNet, 2);
                }

                $remises = [];
                if ($line->isGratuit2020) {
                    $remises[] = 'Gratuité enfant né en 2020';
                }
                if ($cotisation->foyerDiscountPercentage > 0 && $line->montantApresGratuit > 0) {
                    $remises[] = sprintf('Remise fratrie −%d %%', $cotisation->foyerDiscountPercentage);
                }
                if ($inscription && ($inscription->getRemiseManuelle() ?? 0) > 0) {
                    $motif = $inscription->getMotifRemise() ?: 'Remise individuelle';
                    $remises[] = sprintf('%s (−%s €)', $motif, number_format((float) $inscription->getRemiseManuelle(), 2, ',', ' '));
                }

                $coursPages[] = [
                    'coursNom' => $line->coursNom,
                    'jour' => $cours?->getJour() ?? '—',
                    'horaire' => $cours ? $cours->getHeure()->format('H\\hi') : '—',
                    'tarifBrut' => $line->tarifBrut,
                    'remises' => $remises,
                    'montantNet' => $montantNet,
                ];

                $lignesRecap[] = [
                    'danseurNom' => trim($danseur->getPrenom() . ' ' . $danseur->getNom()),
                    'coursNom' => $line->coursNom,
                    'montantNet' => $montantNet,
                ];
            }

            if ($coursPages === []) {
                continue;
            }

            $pagesDanseurs[] = [
                'danseur' => $danseur,
                'cours' => $coursPages,
                'totalNet' => array_sum(array_column($coursPages, 'montantNet')),
            ];
        }

        return $this->render('foyer/facture_ce.html.twig', [
            'foyer' => $foyer,
            'responsable' => $foyer->getUser(),
            'saison' => $saison,
            'cotisation' => $cotisation,
            'pagesDanseurs' => $pagesDanseurs,
            'lignesRecap' => $lignesRecap,
            'numeroDocument' => sprintf(
                'SD430-CE-%s-%04d',
                preg_replace('/\D+/', '', $saison) ?: date('Y'),
                $foyer->getId() ?? 0
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
            'hasVirement' => $inscription->utiliseVirement(),
            'hasCheque' => $inscription->utiliseCheque(),
            'libelleVirement' => $foyer->getReferenceVirement(),
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

        // Facturation / échéancier : réservés au parent principal du foyer.
        return $this->isPrimaryParent($danseur, $user);
    }
}
