<?php

namespace App\Controller\Admin;

use App\Entity\Foyer;
use App\Service\CotisationCalculatorService;
use App\Service\ResteAPayerBadgeFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class FoyerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ResteAPayerBadgeFormatter $resteBadgeFormatter,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Foyer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Foyer / Famille')
            ->setEntityLabelInPlural('Foyers / Familles')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Foyer $f) => 'Famille — '.$f->getNom())
            ->overrideTemplate('crud/detail', 'admin/foyer/detail.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        $saison = CotisationCalculatorService::SAISON_COURANTE;

        yield IdField::new('id')->hideOnForm();

        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('nom', 'Famille')
                ->formatValue(function (?string $value, Foyer $foyer): string {
                    $label = htmlspecialchars($foyer->getNom() ?? '—', \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
                    $url = htmlspecialchars($this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($foyer->getId())
                        ->generateUrl(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

                    return sprintf('<a href="%s" class="fw-semibold text-decoration-none">%s</a>', $url, $label);
                })
                ->renderAsHtml();

            yield AssociationField::new('user', 'Responsable')->onlyOnIndex();

            yield NumberField::new('totalDuSaison', 'Total dû')
                ->setNumDecimals(2)
                ->formatValue(static fn ($v, Foyer $f) => number_format($f->getTotalDuSaison(), 2, ',', ' ').' €')
                ->onlyOnIndex();

            yield NumberField::new('totalDeclareSaisonLabel', 'Déclaré')
                ->formatValue(static fn ($v, Foyer $f) => number_format($f->getTotalDeclareSaison(), 2, ',', ' ').' €')
                ->onlyOnIndex();

            yield NumberField::new('totalEncaisseSaison', 'Encaissé')
                ->setNumDecimals(2)
                ->formatValue(static fn ($v, Foyer $f) => number_format($f->getTotalEncaisseSaison(), 2, ',', ' ').' €')
                ->onlyOnIndex();

            yield TextField::new('resteAPayerBadgeSaisonLabel', 'Reste à payer')
                ->formatValue(fn ($v, Foyer $f) => $this->resteBadgeFormatter->html($f->getResteAPayerSaison()))
                ->renderAsHtml()
                ->onlyOnIndex();

            yield TextField::new('parent2NomComplet', 'Parent 2')
                ->onlyOnIndex();

            return;
        }

        yield TextField::new('nom', 'Nom du Foyer / Famille');
        yield AssociationField::new('user', 'Compte Utilisateur (Parent 1)');
        yield TextField::new('adresse', 'Adresse');
        yield TextField::new('codePostal', 'Code Postal');
        yield TextField::new('ville', 'Ville');
        yield TelephoneField::new('contactUrgence', 'Contact Urgence (Global)');

        yield TextField::new('parent2Prenom', 'Prénom (Parent 2 Foyer)')
            ->onlyOnForms();
        yield TextField::new('parent2Nom', 'Nom (Parent 2 Foyer)')
            ->onlyOnForms();
        yield TelephoneField::new('parent2Telephone', 'Téléphone (Parent 2 Foyer)')
            ->onlyOnForms();
        yield EmailField::new('parent2Email', 'E-mail (Parent 2 Foyer)')
            ->onlyOnForms();

        yield TextField::new('parent2NomComplet', 'Parent 2 (Foyer)');

        yield NumberField::new('totalDuSaison', 'Total dû (saison '.$saison.')')
            ->setNumDecimals(2)
            ->formatValue(static fn ($v, Foyer $f) => number_format($f->getTotalDuSaison(), 2, ',', ' ').' €')
            ->onlyOnDetail();

        yield NumberField::new('totalDeclareSaisonLabel', 'Déclaré par la famille')
            ->formatValue(static fn ($v, Foyer $f) => number_format($f->getTotalDeclareSaison(), 2, ',', ' ').' €')
            ->onlyOnDetail();

        yield NumberField::new('totalEncaisseSaison', 'Total encaissé')
            ->setNumDecimals(2)
            ->formatValue(static fn ($v, Foyer $f) => number_format($f->getTotalEncaisseSaison(), 2, ',', ' ').' €')
            ->onlyOnDetail();

        yield TextField::new('resteAPayerBadgeSaisonLabel', 'Reste à payer')
            ->formatValue(fn ($v, Foyer $f) => $this->resteBadgeFormatter->htmlWithLabel($f->getResteAPayerSaison()))
            ->renderAsHtml()
            ->onlyOnDetail();

        yield NumberField::new('remiseManuelle', 'Remise manuelle (€)')
            ->setNumDecimals(2)
            ->setHelp('Surcharge négative accordée par le bureau, déduite du total cotisation.')
            ->hideOnIndex();
        yield TextField::new('motifRemise', 'Motif de la remise')
            ->hideOnIndex();
    }
}
