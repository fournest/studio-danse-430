<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Form\MediaType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AlbumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Album Galerie')
            ->setEntityLabelInPlural('Galerie Photos & Médias')
            ->setDefaultSort(['dateEvenement' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('titre', 'Titre de l\'événement'),
            DateField::new('dateEvenement', 'Date de l\'événement'),
            TextEditorField::new('description', 'Description / Résumé'),
            CollectionField::new('medias', 'Contenu / Médias')
                ->setEntryType(MediaType::class)
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
                ->onlyOnForms(),
        ];
    }
}
