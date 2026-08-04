<?php

namespace App\Controller\Admin;

use App\Entity\Goodie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class GoodieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Goodie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article boutique')
            ->setEntityLabelInPlural('Boutique (Goodies & Vêtements)')
            ->setDefaultSort(['categorie' => 'ASC', 'nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom', 'Nom');
        yield ChoiceField::new('categorie', 'Catégorie')
            ->setChoices([
                'Vêtement' => 'Vêtement',
                'Accessoire' => 'Accessoire',
                'Goodie' => 'Goodie',
                'Autre' => 'Autre',
            ])
            ->renderAsBadges();
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield MoneyField::new('prixUnitaire', 'Prix unitaire (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2);
        yield ArrayField::new('taillesDisponibles', 'Tailles')
            ->setHelp('Ex. Enfant, XS, S, M, L, XL — une taille par ligne')
            ->hideOnIndex();
        yield IntegerField::new('stock', 'Stock');
        yield TextField::new('imageFile', 'Image')
            ->setFormType(VichImageType::class)
            ->onlyOnForms();
        yield ImageField::new('imageFilename', 'Aperçu')
            ->setBasePath('/uploads/goodies')
            ->onlyOnIndex();
        yield BooleanField::new('estActif', 'Actif')
            ->renderAsSwitch(true);
    }
}
