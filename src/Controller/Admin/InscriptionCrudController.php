<?php

namespace App\Controller\Admin;

use App\Entity\Inscription;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use App\Enum\StatutInscription;
use App\Form\PaiementType;
use App\Service\CotisationCalculatorService;
use App\Service\InscriptionAutofillService;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InscriptionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly InscriptionAutofillService $autofill,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Inscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Inscription')
            ->setEntityLabelInPlural('Inscriptions')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statutChoices = [];
        foreach (StatutInscription::cases() as $case) {
            $statutChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('statut', 'Statut inscription')->setChoices($statutChoices))
            ->add(\EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter::new('estEnListeDAttente', 'Liste d’attente'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $valider = Action::new('validerDefinitivement', 'Valider définitivement', 'fa fa-check-double')
            ->linkToCrudAction('validerDefinitivement')
            ->setCssClass('btn btn-sm btn-success')
            ->displayIf(static fn (Inscription $i) => $i->getStatut() === StatutInscription::EN_ATTENTE_VALIDATION);

        $confirmerAttente = Action::new('confirmerDepuisListeAttente', 'Confirmer (hors liste d’attente)', 'fa fa-user-check')
            ->linkToCrudAction('confirmerDepuisListeAttente')
            ->setCssClass('btn btn-sm btn-warning')
            ->displayIf(static fn (Inscription $i) => $i->isEstEnListeDAttente());

        return $actions
            ->add(Crud::PAGE_INDEX, $valider)
            ->add(Crud::PAGE_DETAIL, $valider)
            ->add(Crud::PAGE_INDEX, $confirmerAttente)
            ->add(Crud::PAGE_DETAIL, $confirmerAttente);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('danseur');
        yield AssociationField::new('cours');
        yield TextField::new('saison');

        yield ChoiceField::new('statut', 'Statut inscription')
            ->setChoices($this->enumChoices(StatutInscription::cases()))
            ->formatValue(static fn ($value, ?Inscription $entity) => $entity?->getStatut()->getLabel() ?? '')
            ->renderAsBadges([
                StatutInscription::BROUILLON->value => 'secondary',
                StatutInscription::EN_ATTENTE_VALIDATION->value => 'warning',
                StatutInscription::VALIDE->value => 'success',
                StatutInscription::ANNULE->value => 'danger',
            ]);

        yield \EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField::new('estEnListeDAttente', 'Liste d’attente')
            ->renderAsSwitch(false);

        yield DateTimeField::new('dateValidation', 'Validée le')->hideOnForm();

        yield MoneyField::new('montantTotal', 'Total (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setDisabled()
            ->setHelp('Recalculé automatiquement via CotisationCalculatorService (cours, remises, boutique).');

        yield NumberField::new('montantRegle', 'Réglé (€)')
            ->setNumDecimals(2)
            ->onlyOnIndex();

        yield NumberField::new('resteAPayer', 'Reste (€)')
            ->setNumDecimals(2)
            ->onlyOnIndex();

        yield TextField::new('indicateurReglement', 'Règlement')
            ->onlyOnDetail();

        yield ChoiceField::new('statutDossier')
            ->setChoices($this->enumChoices(StatutDossier::cases()))
            ->renderAsBadges([
                StatutDossier::EN_ATTENTE->value => 'warning',
                StatutDossier::INCOMPLET->value => 'danger',
                StatutDossier::VALIDE->value => 'success',
            ]);

        yield ChoiceField::new('statutPaiement', 'Statut règlement')
            ->setChoices($this->enumChoices(StatutPaiement::cases()))
            ->renderAsBadges([
                StatutPaiement::NON_PAYE->value => 'danger',
                StatutPaiement::PARTIEL->value => 'warning',
                StatutPaiement::SOLDE->value => 'success',
            ]);

        yield TextField::new('danseur.statutSanteLabel', 'Santé')
            ->onlyOnIndex();

        yield TextField::new('danseur.statutSanteLabel', 'Statut santé')
            ->onlyOnDetail();

        yield TextField::new('modePaiement')->hideOnIndex();

        yield TextField::new('certificatMedical', 'Certificat médical')
            ->formatValue(function ($value, ?Inscription $entity) {
                $danseur = $entity?->getDanseur();
                $filename = $danseur?->getCertificatFilename();
                if (null === $danseur || null === $filename) {
                    $label = \is_string($value) && $value !== ''
                        ? $value
                        : ($danseur?->getStatutSanteLabel() ?? 'Aucun justificatif');

                    return sprintf('<span class="text-muted">%s</span>', htmlspecialchars($label, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'));
                }

                $url = $this->urlGenerator->generate('app_admin_danseur_certificat_download', [
                    'id' => $danseur->getId(),
                ]);

                return sprintf(
                    '<a class="btn btn-sm btn-info" href="%s"><i class="fa fa-file-medical"></i> Télécharger le certificat</a>',
                    htmlspecialchars($url, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                );
            })
            ->renderAsHtml()
            ->setDisabled()
            ->setHelp('Synchronisé depuis le dossier santé du danseur.');

        yield TextField::new('helloAssoPaymentId')
            ->setLabel('Réf. HelloAsso')
            ->setHelp('Référence / reçu saisi par le foyer ou le bureau.');

        yield NumberField::new('remiseManuelle', 'Remise manuelle (€)')
            ->setNumDecimals(2)
            ->setHelp('Remise bureau spécifique à cette inscription. Le total est recalculé à l’enregistrement.')
            ->hideOnIndex();
        yield TextField::new('motifRemise', 'Motif de la remise')
            ->hideOnIndex();

        yield CollectionField::new('paiements', 'Paiements')
            ->setEntryType(PaiementType::class)
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->allowAdd()
            ->allowDelete()
            ->setEntryIsComplex()
            ->hideOnIndex();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Inscription) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $this->autofillBeforeFlush($entityInstance);
        $entityManager->persist($entityInstance);
        $entityManager->flush();
        $this->recalculateAfterFlush($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Inscription) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $this->autofillBeforeFlush($entityInstance);
        $entityManager->persist($entityInstance);
        $entityManager->flush();
        $this->recalculateAfterFlush($entityInstance);
        $entityManager->flush();
    }

    private function autofillBeforeFlush(Inscription $inscription): void
    {
        $this->autofill->syncSante($inscription);
    }

    private function recalculateAfterFlush(Inscription $inscription): void
    {
        $foyer = $inscription->getDanseur()?->getFoyer();
        if (null === $foyer) {
            return;
        }

        $saison = $inscription->getSaison() ?: CotisationCalculatorService::SAISON_COURANTE;
        $detail = $this->autofill->recalculateMontantsForFoyer($foyer, $saison);

        $this->addFlash('info', sprintf(
            'Total foyer recalculé : %s € (cours %s € + extras %s €).',
            number_format($detail->grandTotal, 2, ',', ' '),
            number_format($detail->total, 2, ',', ' '),
            number_format($detail->getExtrasAmount(), 2, ',', ' ')
        ));
    }

    #[AdminRoute('/{entityId}/valider-definitivement', name: 'valider_definitivement')]
    public function validerDefinitivement(AdminContext $context): Response
    {
        /** @var Inscription $inscription */
        $inscription = $context->getEntity()->getInstance();
        $inscription->validerDefinitivement();
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Inscription #%d validée définitivement.', $inscription->getId()));

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    #[AdminRoute('/{entityId}/confirmer-liste-attente', name: 'confirmer_liste_attente')]
    public function confirmerDepuisListeAttente(AdminContext $context): Response
    {
        /** @var Inscription $inscription */
        $inscription = $context->getEntity()->getInstance();
        $saison = CotisationCalculatorService::SAISON_COURANTE;
        $cours = $inscription->getCours();

        if (!$inscription->isEstEnListeDAttente()) {
            $this->addFlash('warning', 'Cette inscription n’est pas en liste d’attente.');
        } elseif ($cours && $cours->estComplet($saison)) {
            $this->addFlash('danger', sprintf(
                'Le cours « %s » est encore complet (%s).',
                $cours->getNom(),
                $cours->getRemplissageLabel($saison)
            ));
        } else {
            $inscription->confirmerDepuisListeAttente();
            $this->entityManager->flush();

            $foyer = $inscription->getDanseur()?->getFoyer();
            if ($foyer) {
                $this->autofill->recalculateMontantsForFoyer($foyer, $saison);
                $this->entityManager->flush();
            }

            $this->addFlash('success', sprintf(
                'Inscription #%d confirmée hors liste d’attente. Cotisation recalculée.',
                $inscription->getId()
            ));
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
