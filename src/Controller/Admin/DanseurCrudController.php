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
        yield TextField::new('prenom');
        yield TextField::new('nom');
        yield DateField::new('dateNaissance')->setLabel('Date de naissance');
        yield AssociationField::new('parent')->setLabel('Parent / Responsable');
        yield AssociationField::new('cours')
            ->setLabel('Cours suivis')
            ->hideOnIndex();
    }
}
