<?php

namespace App\Controller\Admin;

use App\Entity\AchatGoodie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AchatGoodieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AchatGoodie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Achat boutique')
            ->setEntityLabelInPlural('Achats boutique')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('foyer', 'Foyer');
        yield AssociationField::new('goodie', 'Article');
        yield TextField::new('saison', 'Saison');
        yield TextField::new('taille', 'Taille');
        yield IntegerField::new('quantite', 'Quantité');
        yield MoneyField::new('prixUnitaire', 'Prix unitaire (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2);
        yield MoneyField::new('prixTotal', 'Total (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2);
        yield DateTimeField::new('createdAt', 'Commandé le')->hideOnForm();
    }
}
