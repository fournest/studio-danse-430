<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Coordonnées co-parent (Email 2 / Téléphone 2) — non mappées à une entité unique.
 */
class CoParentContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = 'mt-1 block w-full rounded-md bg-black border border-neutral-800 text-white focus:border-[#FFD700] focus:ring-[#FFD700] sm:text-sm px-4 py-2.5 transition-colors';
        $labelClass = 'block text-sm font-medium text-neutral-300';

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email 2 (co-parent)',
                'label_attr' => ['class' => $labelClass],
                'data' => $options['email'],
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => 'coparent@exemple.fr',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L’e-mail est obligatoire.']),
                    new Email(['message' => 'Adresse e-mail invalide.']),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone 2 (co-parent)',
                'label_attr' => ['class' => $labelClass],
                'data' => $options['telephone'],
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => '06 12 34 56 78',
                    'autocomplete' => 'tel',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone est obligatoire.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'email' => '',
            'telephone' => '',
        ]);
        $resolver->setAllowedTypes('email', ['string', 'null']);
        $resolver->setAllowedTypes('telephone', ['string', 'null']);
    }
}
