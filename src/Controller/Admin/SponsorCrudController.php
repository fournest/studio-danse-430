<?php

namespace App\Controller\Admin;

use App\Entity\Sponsor;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SponsorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sponsor::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nom', 'Nom du partenaire ou sponsor'),
            UrlField::new('lien', 'Lien vers leur site web (optionnel)'),

            ImageField::new('logo', 'Logo de l\'entreprise')
                ->setBasePath('uploads/logos/sponsors/')
                ->setUploadDir('public/uploads/logos/sponsors/')
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false), // Change ici pour false pour que ce ne soit plus obligatoire !
        ];
    }
}
