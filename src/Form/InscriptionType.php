<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Danseur;
use App\Entity\Inscription;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('danseur', EntityType::class, [
                'class' => Danseur::class,
                'choice_label' => fn (Danseur $danseur): string => $danseur->getPrenom() . ' ' . $danseur->getNom(),
                'placeholder' => 'Sélectionnez un danseur',
                'label' => 'Danseur à inscrire',
            ])
            // Champ non mappé : l'entité Inscription ne porte qu'un seul cours (ManyToOne).
            // On autorise la sélection multiple ici, puis le contrôleur crée une
            // inscription par cours sélectionné.
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
            ])
            ->add('saison', TextType::class, [
                'label' => 'Saison',
                'empty_data' => '2026/2027',
                'attr' => ['placeholder' => '2026/2027'],
            ])
            ->add('certificatMedical', TextType::class, [
                'required' => false,
                'label' => 'Certificat médical (référence ou nom du fichier)',
                'help' => 'Optionnel pour le moment : vous pourrez le transmettre ultérieurement.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
        ]);
    }
}
