<?php

namespace App\Form;

use App\Entity\Foyer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FoyerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = 'mt-1 block w-full rounded-md bg-black border border-neutral-800 text-white focus:border-[#FFD700] focus:ring-[#FFD700] sm:text-sm px-4 py-2.5 transition-colors';
        $labelClass = 'block text-sm font-medium text-neutral-300';

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du parent référent (ou Nom de la Famille)',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'M. ou Mme DURAND'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du responsable ou de la famille est obligatoire.']),
                ],
            ])
            ->add('telephone', TelType::class, [
                'mapped' => false,
                'label' => 'Téléphone',
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
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse postale',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => '12 rue de la Danse'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse est obligatoire.']),
                ],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => '43000'],
                'constraints' => [
                    new NotBlank(['message' => 'Le code postal est obligatoire.']),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'Le Puy-en-Velay'],
                'constraints' => [
                    new NotBlank(['message' => 'La ville est obligatoire.']),
                ],
            ])
            ->add('contactUrgence', TextType::class, [
                'required' => false,
                'label' => 'Contact d\'urgence (Nom + Téléphone)',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'Papy Durand - 06 00 00 00 00 (Optionnel)'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Foyer::class,
            'telephone' => '',
        ]);
        $resolver->setAllowedTypes('telephone', ['string', 'null']);
    }
}
