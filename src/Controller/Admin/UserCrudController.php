<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField; // 👈 IMPORTANT : Ne pas oublier cet import !
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email');
        yield TextField::new('telephone')->setLabel('Téléphone');
        yield ArrayField::new('roles')->hideOnIndex();
        
        // 🏠 Affichage et gestion du Foyer rattaché à l'utilisateur
        yield AssociationField::new('foyer', 'Foyer / Famille')
            ->setHelp("Le dossier familial associé à ce compte utilisateur.");
        
        // 🔒 Les commutateurs pour le contrôle des accès (UserChecker)
        yield BooleanField::new('isVerified', 'E-mail Vérifié')
            ->setHelp("Coché si l'adhérent a validé son adresse e-mail.");
            
        yield BooleanField::new('isActif', 'Compte Actif')
            ->setHelp("Décochez ce champ pour suspendre immédiatement l'accès de ce parent.");
    }
}