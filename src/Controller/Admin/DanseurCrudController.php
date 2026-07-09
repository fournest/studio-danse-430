<?php

namespace App\Controller\Admin;

use App\Entity\Danseur;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DanseurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Danseur::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom', 'Nom');
        yield DateField::new('dateNaissance')->setLabel('Date de naissance');
        
        // 🏠 On remplace l'ancienne association 'parent' par le 'foyer' pivot
        yield AssociationField::new('foyer', 'Foyer / Famille')
            ->setHelp("Le foyer familial auquel ce danseur est rattaché.");

        yield AssociationField::new('cours', 'Cours suivis')
            ->hideOnIndex();
    }
}