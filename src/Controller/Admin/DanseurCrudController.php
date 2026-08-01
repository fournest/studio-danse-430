<?php

namespace App\Controller\Admin;

use App\Entity\Danseur;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
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

        // 👤 Identité de l'élève
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom', 'Nom');
        yield DateField::new('dateNaissance', 'Date de naissance');

        // 🏠 Rattachement principal
        yield AssociationField::new('foyer', 'Foyer / Famille')
            ->setHelp("Le foyer familial auquel ce danseur est rattaché.");

        // 🎭 Cours
        yield AssociationField::new('cours', 'Cours suivis')
            ->hideOnIndex();

        // 📋 Colonne 2e Parent / Urgence (Visible uniquement sur le listing / Index)
        yield TextField::new('parent2NomComplet', '2ᵉ Parent / Urgence')
            ->onlyOnIndex()
            ->formatValue(function ($value, Danseur $entity) {
                if ($entity->getParent2Nom() || $entity->getParent2Prenom()) {
                    return sprintf('⚠️ %s (Spécifique)', $entity->getParent2NomComplet());
                }
                return $value ?: 'Aucun 2ᵉ parent';
            });

        // 📝 Champs du 2e Parent Spécifique (Formulaires de création / édition)
        yield TextField::new('parent2Prenom', 'Prénom (2ᵉ Parent Spécifique)')
            ->onlyOnForms()
            ->setHelp('À remplir UNIQUEMENT si le 2ᵉ parent de cet enfant est différent de celui du Foyer (famille recomposée, ex-conjoint...).');

        yield TextField::new('parent2Nom', 'Nom (2ᵉ Parent Spécifique)')
            ->onlyOnForms();

        yield TelephoneField::new('parent2Telephone', 'Téléphone (2ᵉ Parent Spécifique)')
            ->onlyOnForms();

        yield EmailField::new('parent2Email', 'E-mail (2ᵉ Parent Spécifique)')
            ->onlyOnForms();
    }
}