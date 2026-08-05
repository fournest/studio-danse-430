<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FlyerConfigType extends AbstractType
{
    private const LABEL_REQUIRED = ['class' => 'flyer-field-required'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'label_attr' => self::LABEL_REQUIRED,
                'attr' => ['placeholder' => 'Ex. : Studio Danse 430'],
                'constraints' => [
                    new NotBlank(message: 'Le titre est obligatoire.'),
                ],
            ])
            ->add('sous_titre', TextType::class, [
                'label' => 'Sous-titre',
                'required' => false,
            ])
            ->add('badge', TextType::class, [
                'label' => 'Badge / Saison',
                'required' => true,
                'label_attr' => self::LABEL_REQUIRED,
                'help' => 'Ex. : INSCRIPTIONS SAISON 2026-2027, ASSEMBLÉE GÉNÉRALE, STAGE DE PRINTEMPS…',
                'constraints' => [
                    new NotBlank(message: 'Le badge / saison est obligatoire.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('tags', TextType::class, [
                'label' => 'Puces / Disciplines',
                'required' => false,
                'help' => 'Séparées par des virgules (mode flyer simple).',
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Mode d\'affichage',
                'label_attr' => self::LABEL_REQUIRED,
                'choices' => [
                    'Flyer simple avec puces' => 'simple',
                    'Flyer complet avec Planning des Cours' => 'planning',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Choisissez un mode d\'affichage.'),
                ],
            ])
            ->add('target', ChoiceType::class, [
                'label' => 'Target URL pour le QR Code',
                'label_attr' => self::LABEL_REQUIRED,
                'choices' => [
                    'Tunnel d\'inscription' => 'inscription',
                    'Page d\'accueil' => 'home',
                    'URL personnalisée' => 'custom',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Choisissez la destination du QR Code.'),
                ],
            ])
            ->add('target_url', UrlType::class, [
                'label' => 'URL personnalisée',
                'required' => false,
                'label_attr' => ['class' => 'flyer-field-conditional-required'],
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://…',
                ],
                'help' => 'Obligatoire uniquement si « URL personnalisée » est sélectionnée.',
            ])
            ->add('publier_dans_fil', CheckboxType::class, [
                'label' => 'Publier cet événement dans le fil d’actualités du site',
                'required' => false,
                'data' => true,
                'help' => 'Si coché, cet événement/flyer apparaîtra automatiquement sur la page d’accueil et la section événements du site public.',
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            /** @var array<string, mixed> $data */
            $data = $event->getData();
            $form = $event->getForm();

            if (($data['target'] ?? '') !== 'custom') {
                return;
            }

            $url = trim((string) ($data['target_url'] ?? ''));
            if ($url === '' || filter_var($url, \FILTER_VALIDATE_URL) === false) {
                $form->get('target_url')->addError(
                    new FormError('L\'URL personnalisée est obligatoire et doit être valide.')
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            // ID session (hors stateless_token_ids) : évite SameOriginCsrfTokenManager + token littéral « csrf-token »
            'csrf_token_id' => 'flyer_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        // Params plats pour /flyer?titre=…&mode=… (compatible FlyerController)
        return '';
    }
}
