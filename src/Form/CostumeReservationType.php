<?php

namespace App\Form;

use App\Entity\Costume;
use App\Entity\ReservationCostume;
use App\Enum\ModeLivraison;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
        /** @var Costume|null $costume */
        $costume = $options['costume'];
        $tailles = $costume instanceof Costume ? $costume->getTaillesAsArray() : [];

        $inputClass = 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400';

        if ($tailles !== []) {
            $builder->add('taille', ChoiceType::class, [
                'label' => 'Taille souhaitée *',
                'choices' => $tailles,
                'placeholder' => 'Choisir une taille',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez choisir une taille.']),
                ],
                'attr' => ['class' => $inputClass],
            ]);
        } else {
            $builder->add('taille', TextType::class, [
                'label' => 'Taille souhaitée *',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer une taille.']),
                ],
                'attr' => [
                    'placeholder' => 'Ex: S, M, 10 ans…',
                    'class' => $inputClass,
                ],
            ]);
        }

        $builder
            ->add('dateEvenement', DateType::class, [
                'label' => 'Date de l\'événement',
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'attr' => ['class' => $inputClass],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début de location',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer une date de début.']),
                ],
                'attr' => ['class' => $inputClass],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin de location',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer une date de fin.']),
                ],
                'attr' => ['class' => $inputClass],
            ])
            ->add('modeLivraison', EnumType::class, [
                'class' => ModeLivraison::class,
                'label' => 'Option de retrait / livraison',
                'expanded' => true,
                'multiple' => false,
                'choice_label' => fn (ModeLivraison $choice) => $choice->getLabel(),
                'attr' => ['class' => 'space-y-2 text-white'],
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
                    'class' => $inputClass,
                ],
            ])
            ->add('remarques', TextareaType::class, [
                'label' => 'Description ou précisions complémentaires',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Précisions sur les accessoires ou le contexte...',
                    'class' => $inputClass,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationCostume::class,
            'costume' => null,
        ]);
        $resolver->setAllowedTypes('costume', ['null', Costume::class]);
    }
}
