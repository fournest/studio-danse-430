<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Danseur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
// Ajout des imports pour la validation
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class DanseurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Correction : Ajout de la classe "border" devant border-neutral-800
        $inputClass = 'mt-1 block w-full rounded-md bg-neutral-900 border border-neutral-800 text-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm px-4 py-2';
        $labelClass = 'block text-sm font-medium text-neutral-300';

        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
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
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'label' => 'Inscrire à un ou plusieurs cours (Optionnel)',
                'label_attr' => ['class' => 'block text-sm font-bold text-amber-500 mb-2 uppercase tracking-wider'],
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'row_attr' => ['class' => 'mt-4 p-4 rounded-lg bg-neutral-900/30 border border-neutral-800 flex flex-col gap-2 text-white']
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