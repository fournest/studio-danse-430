<?php

namespace App\Controller\Admin;

use App\Entity\Inscription;
use App\Entity\Paiement;
use App\Entity\StatutPaiement;
use App\Enum\StatutInscription;
use App\Enum\StatutPaiement as StatutLignePaiement;
use App\Service\FamilleRelanceMailer;
use App\Service\ResteAPayerBadgeFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TRESORIER')]
class ReglementCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ResteAPayerBadgeFormatter $resteBadgeFormatter,
        private readonly EntityManagerInterface $entityManager,
        private readonly FamilleRelanceMailer $familleRelanceMailer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Inscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Règlement')
            ->setEntityLabelInPlural('Règlements')
            ->setPageTitle(Crud::PAGE_INDEX, 'Comptabilité — Règlements (synthèse par inscription)')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Inscription $i) => 'Échéancier — '.$i->getPayeurLabel())
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields([
                'danseur.nom',
                'danseur.prenom',
                'payeurNom',
                'payeurPrenom',
                'danseur.foyer.nom',
                'saison',
            ])
            ->overrideTemplate('crud/detail', 'admin/reglement/detail.html.twig');
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->leftJoin('entity.paiements', 'paiements_filter')
            ->andWhere('entity.montantTotal > 0 OR paiements_filter.id IS NOT NULL')
            ->groupBy('entity.id');

        return $qb;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('statutPaiement', 'Statut règlement')->setChoices([
                'Non payé' => StatutPaiement::NON_PAYE,
                'Partiel' => StatutPaiement::PARTIEL,
                'Soldé' => StatutPaiement::SOLDE,
            ]));
    }

    public function configureActions(Actions $actions): Actions
    {
        $voirInscription = Action::new('voirInscription', 'Fiche inscription', 'fa fa-file-signature')
            ->linkToUrl(fn (Inscription $i) => $this->adminUrlGenerator
                ->setController(InscriptionCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($i->getId())
                ->generateUrl())
            ->addCssClass('btn btn-secondary');

        $relancePaiement = Action::new('relancePaiement', 'Relancer (Retard paiement)', 'fa fa-envelope')
            ->linkToCrudAction('relanceRetardPaiement')
            ->addCssClass('btn btn-warning')
            ->displayIf(static fn (Inscription $i) => $i->hasOverduePaiement());

        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->add(Crud::PAGE_DETAIL, $voirInscription)
            ->add(Crud::PAGE_DETAIL, $relancePaiement)
            ->add(Crud::PAGE_INDEX, $relancePaiement);
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('payeurLabel', 'Famille / Payeur')
                ->formatValue(function (?string $value, Inscription $inscription): string {
                    $label = htmlspecialchars($inscription->getPayeurLabel(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
                    $url = htmlspecialchars($this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($inscription->getId())
                        ->generateUrl(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

                    return sprintf('<a href="%s" class="fw-semibold text-decoration-none">%s</a>', $url, $label);
                })
                ->renderAsHtml();

            yield TextField::new('danseur', 'Danseur')
                ->formatValue(function ($value, Inscription $inscription): string {
                    $danseur = $inscription->getDanseur();
                    if (null === $danseur) {
                        return '—';
                    }
                    $label = htmlspecialchars((string) $danseur, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
                    $url = htmlspecialchars($this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($inscription->getId())
                        ->generateUrl(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

                    return sprintf('<a href="%s" class="text-decoration-none">%s</a>', $url, $label);
                })
                ->renderAsHtml();

            yield TextField::new('saison', 'Saison');

            yield MoneyField::new('montantTotal', 'Total dû')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->setNumDecimals(2);

            yield NumberField::new('montantRegle', 'Encaissé')
                ->setNumDecimals(2)
                ->formatValue(static fn ($value, Inscription $i) => $i->getMontantEncaisse());

            yield NumberField::new('montantDeclare', 'Déclaré')
                ->setNumDecimals(2)
                ->formatValue(static fn ($value, Inscription $i) => $i->getMontantDeclare())
                ->onlyOnIndex();

            yield TextField::new('resteAPayerLabel', 'Reste à payer')
                ->formatValue(fn ($value, Inscription $i) => $this->resteBadgeFormatter->html($i->getResteAPayer()))
                ->renderAsHtml();

            yield TextField::new('echeances', 'Échéances')
                ->formatValue(static fn ($value, Inscription $i) => $i->getEcheances());

            yield ChoiceField::new('statutPaiement', 'Statut')
                ->renderAsBadges([
                    StatutPaiement::NON_PAYE->value => 'danger',
                    StatutPaiement::PARTIEL->value => 'warning',
                    StatutPaiement::SOLDE->value => 'success',
                ]);

            return;
        }

        yield IdField::new('id')->hideOnForm();

        yield TextField::new('payeurLabel', 'Famille / Payeur');
        yield TextField::new('danseur', 'Danseur');
        yield TextField::new('cours', 'Cours');
        yield TextField::new('saison', 'Saison');

        yield MoneyField::new('montantTotal', 'Total dû')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2);

        yield NumberField::new('montantRegle', 'Total encaissé')
            ->setNumDecimals(2)
            ->formatValue(static fn ($value, Inscription $i) => $i->getMontantEncaisse());

        yield NumberField::new('montantDeclare', 'Déclaré par la famille')
            ->setNumDecimals(2)
            ->formatValue(static fn ($value, Inscription $i) => $i->getMontantDeclare())
            ->onlyOnDetail();

        yield TextField::new('resteAPayerLabel', 'Reste à payer')
            ->formatValue(fn ($value, Inscription $i) => $this->resteBadgeFormatter->htmlWithLabel($i->getResteAPayer()))
            ->renderAsHtml();

        yield ChoiceField::new('statutPaiement', 'Statut règlement')
            ->renderAsBadges([
                StatutPaiement::NON_PAYE->value => 'danger',
                StatutPaiement::PARTIEL->value => 'warning',
                StatutPaiement::SOLDE->value => 'success',
            ]);

        yield ChoiceField::new('statut', 'Statut inscription')
            ->formatValue(static fn ($value, ?Inscription $i) => $i?->getStatut()->getLabel() ?? '')
            ->renderAsBadges([
                StatutInscription::BROUILLON->value => 'secondary',
                StatutInscription::EN_ATTENTE_VALIDATION->value => 'warning',
                StatutInscription::VALIDE->value => 'success',
                StatutInscription::ANNULE->value => 'danger',
            ]);

        yield DateTimeField::new('lastPaymentReminderSentAt', 'Dernière relance paiement')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    #[AdminRoute('/{entityId}/encaisser-paiement/{paiementId}', name: 'encaisser_paiement', options: ['methods' => ['GET', 'POST']])]
    public function encaisserPaiement(AdminContext $context, int $paiementId): Response
    {
        /** @var Inscription $inscription */
        $inscription = $context->getEntity()->getInstance();

        $paiement = $this->entityManager->find(Paiement::class, $paiementId);
        if (!$paiement instanceof Paiement) {
            $this->addFlash('danger', 'Ligne de règlement introuvable.');

            return $this->redirectToReglementDetail($inscription);
        }

        if ($paiement->getInscription()?->getId() !== $inscription->getId()) {
            $this->addFlash('danger', 'Cette ligne de règlement ne correspond pas à l\'inscription affichée.');

            return $this->redirectToReglementDetail($inscription);
        }

        if ($paiement->getStatut() === StatutLignePaiement::ENCAISSE) {
            $this->addFlash('info', 'Ce règlement est déjà encaissé.');
        } elseif (!$paiement->canBeEncaisse()) {
            $this->addFlash('warning', 'Ce règlement ne peut pas être validé.');
        } else {
            $paiement->marquerEncaisse();
            $inscription->refreshStatutPaiement();
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                sprintf(
                    'Encaissement validé : %s €.',
                    number_format($paiement->getMontant(), 2, ',', ' ')
                )
            );
        }

        return $this->redirectToReglementDetail($inscription);
    }

    public function relanceRetardPaiement(AdminContext $context): Response
    {
        /** @var Inscription $inscription */
        $inscription = $context->getEntity()->getInstance();

        if (!$inscription->hasOverduePaiement()) {
            $this->addFlash('warning', 'Aucune échéance en retard sur cette inscription.');

            return $this->redirectToReglementDetail($inscription);
        }

        if ($this->familleRelanceMailer->sendRetardPaiement($inscription)) {
            $now = new \DateTimeImmutable();
            foreach ($inscription->getOverduePaiements() as $paiement) {
                $paiement->setLastReminderSentAt($now);
            }
            $inscription->setLastPaymentReminderSentAt($now);
            $this->entityManager->flush();
            $this->addFlash('success', 'Email de relance retard de paiement envoyé.');
        } else {
            $this->addFlash('danger', 'Impossible d’envoyer l’email de relance.');
        }

        return $this->redirectToReglementDetail($inscription);
    }

    private function redirectToReglementDetail(Inscription $inscription): Response
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($inscription->getId())
                ->generateUrl()
        );
    }
}
