<?php

namespace App\Controller\Admin;

use App\Entity\Danseur;
use App\Enum\StatutSante;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DanseurCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Danseur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Danseur')
            ->setEntityLabelInPlural('Danseurs')
            ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC'])
            ->setSearchFields(['nom', 'prenom', 'foyer.nom']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('cours', 'Groupe / Cours')->canSelectMultiple());
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $coursId = $this->getContext()?->getRequest()->query->get('filtreCours');
        if ($coursId) {
            $qb->leftJoin('entity.cours', 'filtre_cours_mm')
                ->leftJoin('entity.inscriptions', 'filtre_insc')
                ->andWhere('filtre_cours_mm.id = :filtreCours OR filtre_insc.cours = :filtreCours')
                ->setParameter('filtreCours', (int) $coursId)
                ->distinct();
        }

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadCertificat = Action::new('downloadCertificat', 'Télécharger le certificat', 'fa fa-file-medical')
            ->linkToRoute('app_admin_danseur_certificat_download', static fn (Danseur $d) => [
                'id' => $d->getId(),
            ])
            ->setCssClass('action-downloadCertificat dropdown-item d-flex align-items-center gap-2')
            ->displayIf(static fn (Danseur $d) => null !== $d->getCertificatFilename());

        $validerSante = Action::new('validerSante', 'Valider le justificatif santé', 'fa fa-check-circle')
            ->linkToCrudAction('approuverSante')
            ->setCssClass('btn btn-sm btn-success')
            ->displayIf(static fn (Danseur $d) => $d->getStatutSante() !== StatutSante::VALIDE_BUREAU);

        $exportGlobal = Action::new('exportGlobal', 'Exporter tous les adhérents', 'fa fa-file-excel')
            ->linkToRoute('app_admin_export_adherents')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-success')
            ->displayIf(fn () => $this->isGranted('ROLE_BUREAU'));

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $downloadCertificat)
            ->add(Crud::PAGE_INDEX, $validerSante)
            ->add(Crud::PAGE_INDEX, $exportGlobal)
            ->add(Crud::PAGE_DETAIL, $downloadCertificat)
            ->add(Crud::PAGE_DETAIL, $validerSante);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Identité', 'fa fa-user');

        yield IdField::new('id')->hideOnForm();
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom', 'Nom');
        yield DateField::new('dateNaissance', 'Date de naissance');

        if (Crud::PAGE_DETAIL === $pageName) {
            yield FormField::addFieldset('Bilan Santé', 'fa fa-heart-pulse');

            yield TextField::new('statutSanteLabel', 'Statut santé global')
                ->formatValue(static function ($value, ?Danseur $entity) {
                    $statut = $entity?->getStatutSante();
                    if (null === $statut) {
                        return '—';
                    }

                    $class = match ($statut) {
                        StatutSante::EN_ATTENTE => 'badge badge-warning',
                        StatutSante::QS_SPORT_VALIDE => 'badge badge-success',
                        StatutSante::CERTIFICAT_FOURNI => 'badge badge-info',
                        StatutSante::VALIDE_BUREAU => 'badge badge-success',
                    };

                    return sprintf('<span class="%s">%s</span>', $class, htmlspecialchars($statut->getLabel(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'));
                })
                ->renderAsHtml();

            yield ChoiceField::new('statutSante', 'Statut santé global')
                ->setChoices($this->enumChoices(StatutSante::cases()))
                ->onlyOnForms();


            yield TextField::new('certificatFilename', 'Certificat médical')
                ->formatValue(function ($value, ?Danseur $entity) {
                    if (!$entity instanceof Danseur || null === $entity->getCertificatFilename()) {
                        return '<span class="text-muted">Aucun certificat déposé</span>';
                    }

                    $url = $this->urlGenerator->generate('app_admin_danseur_certificat_download', [
                        'id' => $entity->getId(),
                    ]);

                    return sprintf(
                        '<a class="btn btn-sm btn-info" href="%s"><i class="fa fa-file-medical"></i> Télécharger le certificat</a>',
                        htmlspecialchars($url, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                    );
                })
                ->renderAsHtml();

            yield DateTimeField::new('dateSignatureQsSport', 'Date / heure d’attestation QS-Sport')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->formatValue(static function ($value, ?Danseur $entity) {
                    return $entity?->getDateSignatureQsSport()?->format('d/m/Y H:i') ?? '—';
                });

            yield BooleanField::new('attestationQsSportValide', 'Attestation QS-Sport validée')
                ->renderAsSwitch(false);

            yield TextareaField::new('remarqueSante', 'Remarques santé (responsable)')
                ->setCssClass('field-remarque-sante')
                ->setTemplatePath('admin/danseur/field_remarque_sante.html.twig');
        } else {
            yield TextField::new('statutSanteLabel', 'Statut santé')
                ->formatValue(static function ($value, ?Danseur $entity) {
                    $statut = $entity?->getStatutSante();
                    if (null === $statut) {
                        return '—';
                    }

                    $class = match ($statut) {
                        StatutSante::EN_ATTENTE => 'badge badge-warning',
                        StatutSante::QS_SPORT_VALIDE => 'badge badge-success',
                        StatutSante::CERTIFICAT_FOURNI => 'badge badge-info',
                        StatutSante::VALIDE_BUREAU => 'badge badge-success',
                    };

                    return sprintf('<span class="%s">%s</span>', $class, htmlspecialchars($statut->getLabel(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'));
                })
                ->renderAsHtml()
                ->onlyOnIndex();

            yield ChoiceField::new('statutSante', 'Statut santé')
                ->setChoices($this->enumChoices(StatutSante::cases()))
                ->onlyOnForms();

            yield BooleanField::new('attestationQsSportValide', 'Attestation QS-Sport')
                ->renderAsSwitch(false)
                ->setDisabled()
                ->hideOnIndex();

            yield DateTimeField::new('dateSignatureQsSport', 'Signature QS-Sport')
                ->hideOnIndex()
                ->setDisabled();

            yield TextField::new('certificatFilename', 'Fichier certificat')
                ->hideOnIndex()
                ->setDisabled();

            yield TextField::new('remarqueSante', 'Remarques santé')
                ->hideOnIndex();
        }

        yield FormField::addFieldset('Foyer / Famille', 'fa fa-house-user');

        yield AssociationField::new('foyer', 'Foyer / Famille')
            ->setHelp('Le foyer familial auquel ce danseur est rattaché.');

        yield AssociationField::new('cours', 'Cours suivis')
            ->setTemplatePath('admin/field/cours_badges.html.twig')
            ->formatValue(static function ($value, ?Danseur $entity) {
                if (!$entity instanceof Danseur) {
                    return [];
                }

                return $entity->getCours();
            });

        yield TextField::new('parent2NomComplet', '2ᵉ Parent / Urgence')
            ->onlyOnIndex()
            ->formatValue(function ($value, Danseur $entity) {
                if ($entity->getParent2Nom() || $entity->getParent2Prenom()) {
                    return sprintf('⚠️ %s (Spécifique)', $entity->getParent2NomComplet());
                }

                return $value ?: 'Aucun 2ᵉ parent';
            });

        yield TextField::new('parent2Prenom', 'Prénom (2ᵉ Parent Spécifique)')
            ->onlyOnForms()
            ->setHelp('À remplir UNIQUEMENT si le 2ᵉ parent de cet enfant est différent de celui du Foyer.');
        yield TextField::new('parent2Nom', 'Nom (2ᵉ Parent Spécifique)')
            ->onlyOnForms();
        yield TelephoneField::new('parent2Telephone', 'Téléphone (2ᵉ Parent Spécifique)')
            ->onlyOnForms();
        yield EmailField::new('parent2Email', 'E-mail (2ᵉ Parent Spécifique)')
            ->onlyOnForms();
    }

    #[AdminRoute('/{entityId}/approuver-sante', name: 'approuver_sante')]
    public function approuverSante(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var Danseur $danseur */
        $danseur = $context->getEntity()->getInstance();
        $danseur->setStatutSante(StatutSante::VALIDE_BUREAU);
        $em->flush();

        $this->addFlash('success', sprintf('Justificatif santé de %s validé par le bureau.', $danseur));

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
