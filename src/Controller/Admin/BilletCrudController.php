<?php

namespace App\Controller\Admin;

use App\Entity\Billet;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BilletCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Billet::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Billet')
            ->setEntityLabelInPlural('Billets événements')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['nomParticipant', 'numeroPlace', 'token', 'user.email']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('event', 'Événement');
        yield AssociationField::new('user', 'Compte');
        yield AssociationField::new('commande', 'Commande boutique')->hideOnIndex();
        yield TextField::new('nomParticipant', 'Participant');
        yield TextField::new('numeroPlace', 'Place')->setRequired(false);
        yield TextField::new('token', 'Token QR')
            ->onlyOnDetail()
            ->setFormTypeOption('disabled', true);
        yield BooleanField::new('estValide', 'Scanné')
            ->renderAsSwitch(false);
        yield DateTimeField::new('scanneA', 'Scanné le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
    }
}
