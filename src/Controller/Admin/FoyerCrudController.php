<?php

namespace App\Controller\Admin;

use App\Entity\Foyer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FoyerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Foyer::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // 🏠 Coordonnées & Parent Principal (Parent 1)
        yield TextField::new('nom', 'Nom du Foyer / Famille');
        yield AssociationField::new('user', 'Compte Utilisateur (Parent 1)');
        yield TextField::new('adresse', 'Adresse');
        yield TextField::new('codePostal', 'Code Postal');
        yield TextField::new('ville', 'Ville');
        yield TelephoneField::new('contactUrgence', 'Contact Urgence (Global)');

        // 👤 Second Parent du Foyer (Formulaires de création / édition)
        yield TextField::new('parent2Prenom', 'Prénom (Parent 2 Foyer)')
            ->onlyOnForms();
        yield TextField::new('parent2Nom', 'Nom (Parent 2 Foyer)')
            ->onlyOnForms();
        yield TelephoneField::new('parent2Telephone', 'Téléphone (Parent 2 Foyer)')
            ->onlyOnForms();
        yield EmailField::new('parent2Email', 'E-mail (Parent 2 Foyer)')
            ->onlyOnForms();

        // 📋 Colonne 2e Parent sur le listing
        yield TextField::new('parent2NomComplet', 'Parent 2 (Foyer)')
            ->onlyOnIndex();

        // 💶 Remise bureau
        yield NumberField::new('remiseManuelle', 'Remise manuelle (€)')
            ->setNumDecimals(2)
            ->setHelp('Surcharge négative accordée par le bureau, déduite du total cotisation.')
            ->hideOnIndex();
        yield TextField::new('motifRemise', 'Motif de la remise')
            ->hideOnIndex();
    }
}