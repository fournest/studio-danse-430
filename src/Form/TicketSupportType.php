<?php

namespace App\Form;

use App\Model\SupportData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketSupportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Votre Nom / Prénom',
                'attr' => [
                    'placeholder' => 'Ex: Jean Dupont',
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse E-mail',
                'attr' => [
                    'placeholder' => 'exemple@domaine.fr',
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('sujet', ChoiceType::class, [
                'label' => 'Type de demande',
                'choices' => [
                    '🐛 Signalement de bug' => 'Signalement de bug',
                    '💡 Demande d\'amélioration' => 'Demande d\'amélioration',
                    '❓ Question / Assistance' => 'Question / Assistance',
                    '📌 Autre' => 'Autre',
                ],
                'attr' => [
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez le problème rencontré ou votre besoin...',
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ])
            ->add('fichier', FileType::class, [
                'label' => 'Pièce jointe (facultatif)',
                'required' => false,
                'attr' => [
                    'class' => 'w-full rounded-lg bg-zinc-900 border border-zinc-700 text-white px-3 py-2 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupportData::class,
        ]);
    }
}