<?php

namespace App\Form;

use App\Enum\ModePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ligne de paiement pour le règlement mixte (foyer) ou l'édition admin.
 */
class PaiementLigneType extends AbstractType
{
    private const INPUT_CLASS = 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mode', EnumType::class, [
                'class' => ModePaiement::class,
                'label' => 'Mode',
                'choice_label' => fn (ModePaiement $choice) => $choice->getLabel(),
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => self::INPUT_CLASS],
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-1'],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'divisor' => 1,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(message: 'Le montant doit être positif.'),
                ],
                'attr' => [
                    'class' => self::INPUT_CLASS . ' js-paiement-montant',
                    'step' => '0.01',
                    'min' => '0.01',
                ],
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-1'],
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
                'attr' => [
                    'class' => self::INPUT_CLASS,
                    'placeholder' => 'Ex. code Pass\'Sport, n° chèque…',
                ],
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-neutral-500 mb-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
