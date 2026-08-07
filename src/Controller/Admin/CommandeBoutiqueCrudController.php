<?php

namespace App\Controller\Admin;

use App\Entity\CommandeBoutique;
use App\Enum\ModePaiementBoutique;
use App\Enum\StatutCommandeBoutique;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class CommandeBoutiqueCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CommandeBoutique::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande boutique')
            ->setEntityLabelInPlural('Commandes boutique')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['nomComplet', 'email', 'telephone']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statutChoices = [];
        foreach (StatutCommandeBoutique::cases() as $statut) {
            $statutChoices[$statut->getLabel()] = $statut->value;
        }

        $modeChoices = [];
        foreach (ModePaiementBoutique::cases() as $mode) {
            $modeChoices[$mode->getLabel()] = $mode->value;
        }

        return $filters
            ->add(ChoiceFilter::new('statut', 'Statut de paiement')->setChoices($statutChoices))
            ->add(ChoiceFilter::new('modePaiement', 'Mode de paiement')->setChoices($modeChoices));
    }

    public function configureActions(Actions $actions): Actions
    {
        $encaisser = Action::new('encaisserPaiement', 'Encaisser le paiement', 'fa fa-check-circle')
            ->linkToCrudAction('encaisserPaiement')
            ->setCssClass('btn btn-sm btn-success')
            ->displayIf(static function (CommandeBoutique $commande) {
                return $commande->getStatut() === StatutCommandeBoutique::EN_ATTENTE_REGLEMENT;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $encaisser)
            ->add(Crud::PAGE_DETAIL, $encaisser)
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nomComplet', 'Nom');
        yield TextField::new('email')->hideOnIndex();
        yield AssociationField::new('foyer', 'Foyer')->hideOnForm();
        yield ChoiceField::new('modePaiement', 'Mode')
            ->setChoices($this->enumChoices(ModePaiementBoutique::cases()))
            ->formatValue(static fn ($value, ?CommandeBoutique $entity) => $entity?->getModePaiement()->getLabel() ?? '')
            ->renderAsBadges();
        yield ChoiceField::new('statut', 'Statut')
            ->setChoices($this->enumChoices(StatutCommandeBoutique::cases()))
            ->formatValue(static fn ($value, ?CommandeBoutique $entity) => $entity?->getStatut()->getLabel() ?? '')
            ->renderAsBadges([
                StatutCommandeBoutique::EN_ATTENTE_REGLEMENT->value => 'warning',
                StatutCommandeBoutique::CONFIRMEE->value => 'info',
                StatutCommandeBoutique::PAYE->value => 'success',
                StatutCommandeBoutique::ANNULE->value => 'danger',
            ]);
        yield MoneyField::new('total', 'Total (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2);
        yield DateTimeField::new('createdAt', 'Commandée le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
        yield DateTimeField::new('dateEncaissement', 'Encaissée le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
    }

    #[AdminRoute('/{entityId}/encaisser-paiement', name: 'encaisser_paiement')]
    public function encaisserPaiement(AdminContext $context): Response
    {
        /** @var CommandeBoutique $commande */
        $commande = $context->getEntity()->getInstance();

        if ($commande->getStatut() !== StatutCommandeBoutique::EN_ATTENTE_REGLEMENT) {
            $this->addFlash('info', 'Cette commande n’est pas en attente de règlement.');
        } else {
            $commande->marquerPaye();
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                sprintf(
                    'Règlement de %s € validé avec succès.',
                    number_format($commande->getTotal(), 2, ',', ' ')
                )
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
     * @param list<\BackedEnum> $cases
     *
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
