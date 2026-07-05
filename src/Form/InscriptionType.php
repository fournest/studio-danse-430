<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Danseur;
use App\Entity\Inscription;
use App\Entity\User;
use App\Repository\DanseurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 🔒 On extrait l'utilisateur connecté
        $user = $options['user'];
        
        // 🏡 On récupère le Foyer lié à cet utilisateur
        $foyer = $user ? $user->getFoyer() : null;

        $inputClass = 'mt-1 block w-full rounded-md bg-neutral-900 border border-neutral-800 text-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm px-4 py-2.5 transition-colors duration-200';
        $labelClass = 'block text-sm font-medium text-neutral-300';

        $builder
            ->add('danseur', EntityType::class, [
                'class' => Danseur::class,
                'choice_label' => fn (Danseur $danseur): string => $danseur->getPrenom() . ' ' . $danseur->getNom(),
                'placeholder' => 'Sélectionnez un danseur',
                'label' => 'Danseur à inscrire',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass],
                // 🔒 CLÉ DE SÉCURITÉ MISE À JOUR : On filtre les danseurs qui appartiennent au FOYER de l'utilisateur
                'query_builder' => function (DanseurRepository $dr) use ($foyer) {
                    return $dr->createQueryBuilder('d')
                        ->where('d.foyer = :foyer')
                        ->setParameter('foyer', $foyer);
                },
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un élève.']),
                ],
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => fn (Cours $cours): string => sprintf(
                    '%s — %s %s',
                    $cours->getNom(),
                    $cours->getJour(),
                    $cours->getHeure()->format('H\hi')
                ),
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'label' => 'Cours souhaité(s)',
                'label_attr' => ['class' => 'block text-sm font-bold text-amber-500 mb-2 uppercase tracking-wider'],
                'row_attr' => ['class' => 'mt-4 p-4 rounded-lg bg-neutral-900/30 border border-neutral-800 flex flex-col gap-2 text-white'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner au moins un cours.']),
                ],
            ])
            ->add('saison', TextType::class, [
                'label' => 'Saison',
                'label_attr' => ['class' => $labelClass],
                'empty_data' => '2026/2027',
                'attr' => ['class' => $inputClass, 'placeholder' => '2026/2027'],
                'constraints' => [
                    new NotBlank(['message' => 'La saison est obligatoire.']),
                ],
            ])
            ->add('certificatMedical', TextType::class, [
                'required' => false,
                'label' => 'Certificat médical (référence ou nom du fichier)',
                'label_attr' => ['class' => $labelClass],
                'attr' => ['class' => $inputClass, 'placeholder' => 'Optionnel pour le moment'],
                'help' => 'Vous pourrez transmettre ce document ultérieurement depuis votre espace.',
                'help_attr' => ['class' => 'text-xs text-neutral-400 mt-1 block'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', [User::class, 'null']);
    }
}