<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = 'mt-1 block w-full rounded-md bg-neutral-900 border border-neutral-800 text-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm px-4 py-2.5 transition-colors duration-200';
        $labelClass = 'block text-sm font-medium text-neutral-300';

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'exemple@mail.com'],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Numéro de téléphone',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => '06 12 34 56 78'],
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone est obligatoire pour l\'association.']),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'autocomplete' => 'new-password', 'placeholder' => '••••••••'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => "J'accepte les conditions d'utilisation",
                'label_attr' => ['class' => 'ml-2 text-sm text-neutral-300'],
                'attr' => ['class' => 'rounded bg-neutral-900 border-neutral-800 text-amber-500 focus:ring-amber-500'],
                'constraints' => [
                    new IsTrue(['message' => 'Vous devez accepter nos conditions.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}