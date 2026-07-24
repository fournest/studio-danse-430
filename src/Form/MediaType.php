<?php

namespace App\Form;

use App\Entity\Media;
use App\Enum\TypeMedia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'class' => TypeMedia::class,
                'label' => 'Type de média',
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Fichier image (si image locale)',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
            ])
            ->add('embedUrl', UrlType::class, [
                'label' => 'Lien du post / URL vidéo',
                'required' => false,
            ])
            ->add('legende', TextType::class, [
                'label' => 'Légende (facultatif)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}