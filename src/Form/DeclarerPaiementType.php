<?php

namespace App\Form;

use App\Enum\ModePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

final class DeclarerPaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $modesFamille = [
            ModePaiement::VIREMENT,
            ModePaiement::CHEQUE,
            ModePaiement::PASS_SPORT,
            ModePaiement::ANCV,
            ModePaiement::ESPECES,
        ];

        $builder
            ->add('mode', EnumType::class, [
                'class' => ModePaiement::class,
                'choices' => $modesFamille,
                'label' => 'Mode de paiement',
                'choice_label' => static fn (ModePaiement $mode) => $mode->getLabel(),
                'constraints' => [new NotBlank()],
            ])
            ->add('montant', NumberType::class, [
                'label' => 'Montant réglé (€)',
                'html5' => true,
                'scale' => 2,
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan(0),
                ],
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence / n° de virement ou chèque (facultatif)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'declarer_paiement',
        ]);
    }
}
