<?php

namespace App\Form;

use App\Entity\Paiement;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement as StatutLignePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'édition d'un Paiement (CollectionField EasyAdmin / admin).
 */
class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'divisor' => 1,
            ])
            ->add('mode', EnumType::class, [
                'class' => ModePaiement::class,
                'label' => 'Mode',
                'choice_label' => fn (ModePaiement $c) => $c->getLabel(),
            ])
            ->add('statut', EnumType::class, [
                'class' => StatutLignePaiement::class,
                'label' => 'Statut',
                'choices' => StatutLignePaiement::storableCases(),
                'choice_label' => fn (StatutLignePaiement $c) => $c->getLabel(),
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
            ])
            ->add('emetteur', TextType::class, [
                'label' => 'Émetteur',
                'required' => false,
            ])
            ->add('dateEncaissementPrevue', DateType::class, [
                'label' => 'Encaissement prévu',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('dateEncaissementReelle', DateType::class, [
                'label' => 'Encaissement réel',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('remarques', TextareaType::class, [
                'label' => 'Remarques',
                'required' => false,
                'attr' => ['rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
        ]);
    }
}
