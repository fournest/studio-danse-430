<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Enum\TypeMedia;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Media::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('album', 'Album associé'),
            ChoiceField::new('type', 'Type de média')
                ->setChoices([
                    '📷 Image locale' => TypeMedia::IMAGE_LOCAL,
                    '📸 Post Instagram' => TypeMedia::INSTAGRAM,
                    '📘 Post Facebook'  => TypeMedia::FACEBOOK,
                    '🎬 Vidéo YouTube'  => TypeMedia::YOUTUBE,
                ]),
            TextField::new('imageFile', 'Fichier image (si image locale)')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),
            ImageField::new('imageName', 'Aperçu')
                ->setBasePath('/uploads/galerie')
                ->onlyOnIndex(),
            UrlField::new('embedUrl', 'Lien du post / URL vidéo')
                ->setHelp('Collez l\'URL complète du post Instagram, Facebook ou de la vidéo YouTube'),
            TextField::new('legende', 'Légende (facultatif)'),
        ];
    }
}