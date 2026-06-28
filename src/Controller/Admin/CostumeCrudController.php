<?php

namespace App\Controller\Admin;

use App\Entity\Costume;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class CostumeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Costume::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nom', 'Nom du costume');
        yield TextField::new('taille', 'Taille / Âge');
        
        // 💰 Gestion du prix (affiché en Euros)
        yield IntegerField::new('prix', 'Prix de la location (€/WE)');
        
        // 📦 Gestion du stock disponible
        yield IntegerField::new('quantite', 'Nombre d\'exemplaires');
        
        yield TextEditorField::new('description', 'Consignes & Accessoires')
            ->hideOnIndex();

        
        yield ImageField::new('photo', 'Photo du costume')
            ->setBasePath('uploads/costumes')
            ->setUploadDir('public/uploads/costumes')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
    }
}