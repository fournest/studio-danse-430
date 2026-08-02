<?php

namespace App\Form;

use App\Entity\Danseur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
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
                        'max' => 50,
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
                        'max' => 50,
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
                        'message' => 'La date de naissance ne peut pas être dans le futur.',
                    ]),
                ],
            ])
            ->add('parent2Prenom', TextType::class, [
                'required' => false,
                'label' => 'Prénom du second parent',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'Marie'],
                'help' => 'Second Parent / Co-parent (facultatif)',
                'help_attr' => ['class' => 'mt-1 text-xs text-neutral-500'],
            ])
            ->add('parent2Nom', TextType::class, [
                'required' => false,
                'label' => 'Nom du second parent',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'Martin'],
            ])
            ->add('parent2Email', EmailType::class, [
                'required' => false,
                'label' => 'E-mail du second parent',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'coparent@mail.com'],
                'help' => 'Permet d’envoyer un accès en lecture à l’autre parent.',
                'help_attr' => ['class' => 'mt-1 text-xs text-neutral-500'],
                'constraints' => [
                    new Email(['message' => 'Saisissez une adresse e-mail valide.']),
                ],
            ])
            ->add('parent2Telephone', TelType::class, [
                'required' => false,
                'label' => 'Téléphone du second parent',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => '06 12 34 56 78'],
            ]);

        if ($options['allow_resend_invite']) {
            $builder->add('renvoyerInvitation', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Renvoyer l’invitation e-mail au co-parent',
                'label_attr' => ['class' => 'ml-2 text-sm text-neutral-300'],
                'attr' => ['class' => 'rounded bg-neutral-900 border-neutral-800 text-amber-500 focus:ring-amber-500'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Danseur::class,
            'allow_resend_invite' => false,
        ]);
        $resolver->setAllowedTypes('allow_resend_invite', 'bool');
    }
}
