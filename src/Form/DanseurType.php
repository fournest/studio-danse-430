<?php

namespace App\Form;

use App\Entity\Danseur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class DanseurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = 'mt-1 block w-full rounded-md bg-neutral-900 border border-neutral-800 text-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm px-4 py-2.5 transition-colors duration-200';
        $labelClass = 'block text-sm font-medium text-neutral-300';

        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom du danseur',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'Jean'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire.']),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le prénom doit faire au moins {{ limit }} caractères.',
                        'max' => 50
                    ]),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de famille',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'Dupont'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de famille est obligatoire.']),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom doit faire au moins {{ limit }} caractères.',
                        'max' => 50
                    ]),
                ],
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'label_attr' => ['class' => $labelClass],
                'widget' => 'single_text',
                'attr' => ['class' => $inputClass],
                'constraints' => [
                    new NotBlank(['message' => 'La date de naissance est obligatoire.']),
                    new LessThan([
                        'value' => 'today',
                        'message' => 'La date de naissance ne peut pas être dans le futur.'
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Danseur::class,
        ]);
    }
}