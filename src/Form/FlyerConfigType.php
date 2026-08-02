<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlyerConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => true,
            ])
            ->add('sous_titre', TextType::class, [
                'label' => 'Sous-titre',
                'required' => false,
            ])
            ->add('badge', TextType::class, [
                'label' => 'Badge / Saison',
                'required' => true,
                'help' => 'Ex. : INSCRIPTIONS SAISON 2026-2027, ASSEMBLÉE GÉNÉRALE, STAGE DE PRINTEMPS…',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('tags', TextType::class, [
                'label' => 'Puces / Disciplines',
                'required' => false,
                'help' => 'Séparées par des virgules (mode flyer simple).',
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Mode d\'affichage',
                'choices' => [
                    'Flyer simple avec puces' => 'simple',
                    'Flyer complet avec Planning des Cours' => 'planning',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('target', ChoiceType::class, [
                'label' => 'Target URL pour le QR Code',
                'choices' => [
                    'Tunnel d\'inscription' => 'inscription',
                    'Page d\'accueil' => 'home',
                    'URL personnalisée' => 'custom',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('target_url', UrlType::class, [
                'label' => 'URL personnalisée',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://…',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        // Params plats pour /flyer?titre=…&mode=… (compatible FlyerController)
        return '';
    }
}
