<?php

namespace App\Form;

use App\Entity\ReservationCostume;
use App\Enum\ModeLivraison;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CostumeReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taille', TextType::class, [
                'label' => 'Taille souhaitée',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: S, M, 10 ans...',
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('dateEvenement', DateType::class, [
                'label' => 'Date de l\'événement',
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'attr' => [
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début de location',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer une date de début.']),
                ],
                'attr' => [
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin de location',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer une date de fin.']),
                ],
                'attr' => [
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('modeLivraison', EnumType::class, [
                'class' => ModeLivraison::class,
                'label' => 'Option de livraison',
                'expanded' => true, // Affiche des boutons radio
                'multiple' => false,
                'choice_label' => fn (ModeLivraison $choice) => $choice->getLabel(),
                'attr' => [
                    'class' => 'space-y-2 text-white',
                ],
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'data' => 1,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
                'attr' => [
                    'min' => 1,
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('remarques', TextareaType::class, [
                'label' => 'Description ou précisions complémentaires',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Précisions sur les accessoires ou le contexte...',
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationCostume::class,
        ]);
    }
}