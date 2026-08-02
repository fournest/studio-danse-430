<?php

namespace App\Controller\Admin;

use App\Entity\Cours;
use App\Entity\Inscription;
use App\Repository\InscriptionRepository;
use App\Service\CotisationCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class CoursCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InscriptionRepository $inscriptionRepository,
        private readonly CotisationCalculatorService $cotisationCalculator,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Cours::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Cours')
            ->setEntityLabelInPlural('Cours')
            ->setDefaultSort(['jour' => 'ASC', 'heure' => 'ASC'])
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (Cours $c) => $c->getNom())
            ->overrideTemplate('crud/detail', 'admin/cours/detail.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $filtreComplets = Action::new('filtreComplets', 'Cours complets', 'fa fa-users-slash')
            ->linkToUrl(
                fn () => $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->set('filtreComplets', '1')
                    ->generateUrl()
            )
            ->createAsGlobalAction()
            ->setCssClass('btn btn-warning');

        $tous = Action::new('tousCours', 'Tous les cours', 'fa fa-list')
            ->linkToUrl(
                fn () => $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->unset('filtreComplets')
                    ->generateUrl()
            )
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $filtreComplets)
            ->add(Crud::PAGE_INDEX, $tous)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($this->getContext()?->getRequest()->query->get('filtreComplets') === '1') {
            $saison = CotisationCalculatorService::SAISON_COURANTE;
            $qb->andWhere(
                'entity.capaciteMax <= (
                    SELECT COUNT(i.id) FROM App\Entity\Inscription i
                    WHERE i.cours = entity
                      AND i.saison = :saisonComplet
                      AND i.estEnListeDAttente = false
                      AND i.statut IN (:statutsOccupants)
                )'
            )
                ->setParameter('saisonComplet', $saison)
                ->setParameter('statutsOccupants', [
                    \App\Enum\StatutInscription::BROUILLON,
                    \App\Enum\StatutInscription::EN_ATTENTE_VALIDATION,
                    \App\Enum\StatutInscription::VALIDE,
                ]);
        }

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        $saison = CotisationCalculatorService::SAISON_COURANTE;

        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nom');
        yield TextField::new('jour');
        yield TimeField::new('heure');
        yield TextField::new('professeur', 'Professeur(s)')
            ->setHelp('Nom(s) uniquement (pas d’email). Plusieurs professeurs : séparez par une virgule, ex. « Marie Dupont, Jean Martin ».');
        yield ChoiceField::new('dureeMinutes', 'Durée')
            ->setChoices([
                '1h00' => 60,
                '1h15' => 75,
                '1h30' => 90,
            ]);
        yield NumberField::new('tarif', 'Tarif (€)')
            ->setNumDecimals(2)
            ->setHelp('Grille tarifaire saison — utilisée par le calculateur de cotisations.');
        yield IntegerField::new('anneeNaissanceMin', 'Année naissance min')
            ->setHelp('Ex. 2008 pour Enfants/Ados. Laisser vide = pas de borne.')
            ->hideOnIndex();
        yield IntegerField::new('anneeNaissanceMax', 'Année naissance max')
            ->setHelp('Ex. 2022 pour Enfants/Ados ; 2007 pour Adultes.')
            ->hideOnIndex();
        yield IntegerField::new('capaciteMax')->setLabel('Capacité max');

        yield TextField::new('remplissageLabel', 'Effectif')
            ->formatValue(static function ($value, ?Cours $cours) use ($saison) {
                if (!$cours) {
                    return '';
                }
                $inscrits = $cours->getNombreInscrits($saison);
                $max = $cours->getCapaciteMax();
                $pct = $max > 0 ? (int) round(($inscrits / $max) * 100) : 0;
                $badge = $cours->estComplet($saison) ? '🔴' : ($pct >= 80 ? '🟠' : '🟢');

                return sprintf('%s %d / %d (%d %%)', $badge, $inscrits, $max, $pct);
            })
            ->onlyOnIndex();

        yield TextField::new('listeAttenteResume', 'Liste d’attente')
            ->formatValue(static function ($value, ?Cours $cours) use ($saison) {
                if (!$cours) {
                    return '';
                }
                $n = \count($cours->getInscriptionsListeAttente($saison));

                return $n > 0 ? sprintf('%d élève(s)', $n) : '—';
            })
            ->onlyOnIndex();

        yield UrlField::new('whatsappGroupLink')
            ->setLabel('Lien groupe WhatsApp')
            ->hideOnIndex();
    }

    public function configureResponseParameters(\EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore $responseParameters): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore
    {
        if (Crud::PAGE_DETAIL === $responseParameters->get('pageName')) {
            /** @var Cours|null $cours */
            $cours = $responseParameters->get('entity')?->getInstance();
            if ($cours instanceof Cours) {
                $saison = CotisationCalculatorService::SAISON_COURANTE;
                $responseParameters->set('listeAttente', $this->inscriptionRepository->findListeAttenteByCours($cours, $saison));
                $responseParameters->set('saisonCourante', $saison);
                $responseParameters->set('effectif', [
                    'inscrits' => $cours->getNombreInscrits($saison),
                    'capacite' => $cours->getCapaciteMax(),
                    'restantes' => $cours->getPlacesRestantes($saison),
                    'complet' => $cours->estComplet($saison),
                ]);
            }
        }

        return $responseParameters;
    }

    #[AdminRoute('/{entityId}/confirmer-attente', name: 'confirmer_attente')]
    public function confirmerListeAttente(AdminContext $context, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        /** @var Cours $cours */
        $cours = $context->getEntity()->getInstance();
        $saison = CotisationCalculatorService::SAISON_COURANTE;
        $inscriptionId = (int) $request->query->get('inscriptionId', 0);

        $inscription = $this->inscriptionRepository->find($inscriptionId);
        if (!$inscription instanceof Inscription
            || $inscription->getCours()?->getId() !== $cours->getId()
            || !$inscription->isEstEnListeDAttente()
        ) {
            $this->addFlash('danger', 'Inscription en liste d’attente introuvable.');

            return $this->redirectToCoursDetail($cours);
        }

        if ($cours->estComplet($saison)) {
            $this->addFlash('danger', sprintf(
                'Le cours « %s » est encore complet (%s). Libérez une place avant de confirmer.',
                $cours->getNom(),
                $cours->getRemplissageLabel($saison)
            ));

            return $this->redirectToCoursDetail($cours);
        }

        $inscription->confirmerDepuisListeAttente();
        $this->entityManager->flush();

        $foyer = $inscription->getDanseur()?->getFoyer();
        if ($foyer) {
            $detail = $this->cotisationCalculator->calculateForFoyer($foyer, $saison);
            $this->repartirMontantsFoyer($foyer, $saison, $detail);
            $this->entityManager->flush();
        }

        $this->addFlash('success', sprintf(
            '%s confirmé(e) sur « %s ». Cotisation recalculée.',
            $inscription->getDanseur(),
            $cours->getNom()
        ));

        return $this->redirectToCoursDetail($cours);
    }

    private function redirectToCoursDetail(Cours $cours): Response
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($cours->getId())
                ->generateUrl()
        );
    }

    private function repartirMontantsFoyer(\App\Entity\Foyer $foyer, string $saison, \App\Dto\CotisationDetail $detail): void
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
}
