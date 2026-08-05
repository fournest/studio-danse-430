<?php

namespace App\Controller\Admin;

use App\Entity\Paiement;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement as StatutLignePaiement;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class PaiementCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Paiement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Règlement')
            ->setEntityLabelInPlural('Règlements')
            ->setPageTitle(Crud::PAGE_INDEX, 'Comptabilité — Règlements')
            ->setDefaultSort(['dateEncaissementPrevue' => 'ASC', 'id' => 'ASC'])
            ->setSearchFields(['reference', 'emetteur', 'remarques', 'inscription.danseur.nom', 'inscription.danseur.prenom']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $modeChoices = [];
        foreach (ModePaiement::cases() as $mode) {
            $modeChoices[$mode->getLabel()] = $mode->value;
        }

        $statutChoices = [];
        foreach (StatutLignePaiement::storableCases() as $statut) {
            $statutChoices[$statut->getLabel()] = $statut->value;
        }

        return $filters
            ->add(ChoiceFilter::new('mode', 'Mode')->setChoices($modeChoices))
            ->add(ChoiceFilter::new('statut', 'Statut')->setChoices($statutChoices))
            ->add(DateTimeFilter::new('dateEncaissementPrevue', 'Encaissement prévu'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $encaisser = Action::new('encaisser', 'Valider l\'encaissement', 'fa fa-check-circle')
            ->linkToCrudAction('encaisser')
            ->setCssClass('btn btn-sm btn-success')
            ->displayIf(static function (Paiement $paiement) {
                return $paiement->canBeEncaisse();
            });

        $validerHelloAsso = Action::new('validerHelloAsso', 'Valider le règlement HelloAsso', 'fa fa-credit-card')
            ->linkToCrudAction('validerHelloAsso')
            ->setCssClass('btn btn-sm btn-warning')
            ->displayIf(static function (Paiement $paiement) {
                return $paiement->getMode() === ModePaiement::HELLOASSO
                    && $paiement->getStatut() !== StatutLignePaiement::ENCAISSE;
            });

        $batchEncaisse = Action::new('marquerEncaisseBatch', 'Marquer comme encaissé')
            ->linkToCrudAction('marquerEncaisseBatch')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check-double');

        return $actions
            ->add(Crud::PAGE_INDEX, $encaisser)
            ->add(Crud::PAGE_INDEX, $validerHelloAsso)
            ->add(Crud::PAGE_DETAIL, $encaisser)
            ->add(Crud::PAGE_DETAIL, $validerHelloAsso)
            ->addBatchAction($batchEncaisse);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('inscription', 'Inscription');

        yield MoneyField::new('montant', 'Montant')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2);

        yield ChoiceField::new('mode', 'Mode')
            ->setChoices($this->enumChoices(ModePaiement::cases()))
            ->formatValue(static fn ($value, ?Paiement $entity) => $entity?->getMode()->getLabel() ?? '')
            ->renderAsBadges([
                ModePaiement::CHEQUE->value => 'primary',
                ModePaiement::ANCV->value => 'info',
                ModePaiement::PASS_SPORT->value => 'success',
                ModePaiement::VIREMENT->value => 'secondary',
                ModePaiement::ESPECES->value => 'warning',
                ModePaiement::HELLOASSO->value => 'danger',
            ]);

        yield ChoiceField::new('statut', 'Statut')
            ->setChoices($this->enumChoices(StatutLignePaiement::storableCases()))
            ->formatValue(static fn ($value, ?Paiement $entity) => $entity?->getStatutAffiche()->getLabel() ?? '')
            ->renderAsBadges([
                StatutLignePaiement::EN_ATTENTE->value => 'warning',
                StatutLignePaiement::RECU->value => 'info',
                StatutLignePaiement::PAIEMENT_DECLARE->value => 'info',
                StatutLignePaiement::ENCAISSE->value => 'success',
                StatutLignePaiement::REFUSE->value => 'danger',
                StatutLignePaiement::RETARD->value => 'danger',
            ]);

        yield TextField::new('reference', 'Référence / reçu HelloAsso');
        yield TextField::new('emetteur', 'Émetteur');
        yield DateField::new('dateEncaissementPrevue', 'Prévu le');
        yield DateField::new('dateEncaissementReelle', 'Encaissé le')->hideOnIndex();
        yield TextareaField::new('remarques', 'Remarques')->hideOnIndex();
    }

    #[AdminRoute('/{entityId}/encaisser', name: 'encaisser')]
    public function encaisser(AdminContext $context): Response
    {
        /** @var Paiement $paiement */
        $paiement = $context->getEntity()->getInstance();
        if ($paiement->canBeEncaisse()) {
            $paiement->marquerEncaisse();
            $paiement->getInscription()?->refreshStatutPaiement();
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Encaissement validé pour le paiement #%d.', $paiement->getId()));
        } else {
            $this->addFlash('info', 'Ce paiement ne peut pas être validé.');
        }

        return $this->redirectAfterEncaissement($paiement);
    }

    #[AdminRoute('/{entityId}/valider-helloasso', name: 'valider_helloasso')]
    public function validerHelloAsso(AdminContext $context): Response
    {
        /** @var Paiement $paiement */
        $paiement = $context->getEntity()->getInstance();
        $paiement->marquerEncaisse();
        $inscription = $paiement->getInscription();
        $inscription?->refreshStatutPaiement();
        if ($inscription && $inscription->getStatut() === \App\Enum\StatutInscription::EN_ATTENTE_VALIDATION) {
            $inscription->validerDefinitivement();
        }
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Règlement HelloAsso #%d validé et encaissé.', $paiement->getId()));

        return $this->redirectAfterEncaissement($paiement);
    }

    #[AdminRoute('/marquer-encaisse-batch', name: 'marquer_encaisse_batch')]
    public function marquerEncaisseBatch(BatchActionDto $batchActionDto): Response
    {
        $count = 0;
        $lastInscriptionId = null;
        foreach ($batchActionDto->getEntityIds() as $id) {
            $paiement = $this->entityManager->find(Paiement::class, $id);
            if (!$paiement instanceof Paiement) {
                continue;
            }
            $paiement->marquerEncaisse();
            $paiement->getInscription()?->refreshStatutPaiement();
            $lastInscriptionId = $paiement->getInscription()?->getId() ?? $lastInscriptionId;
            ++$count;
        }
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d règlement(s) marqué(s) comme encaissé(s).', $count));

        if (null !== $lastInscriptionId) {
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(ReglementCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($lastInscriptionId)
                    ->generateUrl()
            );
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(ReglementCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    private function redirectAfterEncaissement(Paiement $paiement): Response
    {
        $inscriptionId = $paiement->getInscription()?->getId();
        if (null !== $inscriptionId) {
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(ReglementCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($inscriptionId)
                    ->generateUrl()
            );
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    /**
     * @param \BackedEnum[] $cases
     * @return array<string, \BackedEnum>
     */
    private function enumChoices(array $cases): array
    {
        $choices = [];
        foreach ($cases as $case) {
            $label = method_exists($case, 'getLabel') ? $case->getLabel() : (string) $case->value;
            $choices[$label] = $case;
        }

        return $choices;
    }
}
