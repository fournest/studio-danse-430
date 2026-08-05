<?php

namespace App\Controller\Admin;

use App\Entity\Actualite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ActualiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Actualite::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Actualité / Événement')
            ->setEntityLabelInPlural('Actualités & Événements')
            ->setPageTitle(Crud::PAGE_INDEX, 'Actualités & événements du club')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['titre', 'chapeau', 'contenu', 'slug']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('titre', 'Titre');

        yield SlugField::new('slug', 'Slug URL')
            ->setTargetFieldName('titre')
            ->hideOnIndex();

        yield TextareaField::new('chapeau', 'Chapeau / résumé')
            ->hideOnIndex()
            ->setHelp('Court résumé affiché sur les cartes et la liste.');

        yield TextEditorField::new('contenu', 'Contenu')
            ->hideOnIndex();

        yield ImageField::new('image', 'Image')
            ->setBasePath('uploads/actualites')
            ->setUploadDir('public/uploads/actualites')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false);

        yield BooleanField::new('isPublished', 'Publiée')
            ->renderAsSwitch(true);

        yield BooleanField::new('publierDansFil', 'Afficher dans le fil d’actualités')
            ->renderAsSwitch(true)
            ->setHelp('Décochez pour un flyer ou événement visible uniquement via son lien direct (hors accueil et liste).');

        yield DateTimeField::new('createdAt', 'Date de création')
            ->setFormTypeOption('disabled', $pageName === Crud::PAGE_NEW)
            ->hideOnForm();
    }
}
