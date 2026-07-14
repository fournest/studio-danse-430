<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext; // Ajout nécessaire
use Doctrine\ORM\EntityManagerInterface; // Ajout nécessaire
use Symfony\Component\HttpFoundation\Request;

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

        yield AssociationField::new('foyer', 'Foyer / Famille')
            ->setHelp("Le dossier familial associé à ce compte utilisateur.");

        yield BooleanField::new('isVerified', 'E-mail Vérifié');
        yield BooleanField::new('isActif', 'Compte Actif');
    }

    public function configureActions(Actions $actions): Actions
    {
        // 1. Action personnalisée "Bannir"
        $ban = Action::new('ban', 'Bannir / Débannir', 'fa fa-ban')
            ->linkToCrudAction('banUser')
            ->addCssClass('text-red-500');

        // 2. Mise à jour de l'action native "DELETE"
        // On utilise setHtmlAttributes à la place de setConfirmationMessage
        $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
            return $action->setHtmlAttributes([
                'data-confirm' => 'Êtes-vous sûr de vouloir supprimer cet utilisateur définitivement ?'
            ]);
        });

        return $actions->add(Crud::PAGE_INDEX, $ban);
    }
}