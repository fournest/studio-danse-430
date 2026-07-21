<?php

namespace App\Controller\Admin;

use App\Entity\ReservationCostume;
use App\Enum\StatutReservation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReservationCostumeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ReservationCostume::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réservation de costume')
            ->setEntityLabelInPlural('Réservations de costumes')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('costume', 'Costume'),
            AssociationField::new('user', 'Demandeur'),
            TextField::new('taille', 'Taille'),
            IntegerField::new('quantite', 'Quantité'),
            DateField::new('dateEvenement', 'Date événement'),
            DateField::new('dateDebut', 'Début location'),
            DateField::new('dateFin', 'Fin location'),
            
            // Mode de livraison lisible
            ChoiceField::new('modeLivraison', 'Mode de livraison')
                ->renderAsBadges([
                    'RETRAIT_LOCAUX' => 'info',
                    'POINT_RELAIS' => 'secondary',
                ]),

            MoneyField::new('prixTotal', 'Prix Total')->setCurrency('EUR')->setStoredAsCents(false),

            // Statut avec badges colorés
            ChoiceField::new('statut', 'Statut')
                ->renderAsBadges([
                    StatutReservation::EN_ATTENTE->value => 'warning',   // Jaune / Orange
                    StatutReservation::VALIDEE->value    => 'success',   // Vert
                    StatutReservation::REFUSEE->value    => 'danger',    // Rouge
                    StatutReservation::RESTITUEE->value   => 'info',      // Bleu
                    StatutReservation::ANNULEE->value    => 'secondary', // Gris
                ]),

            DateTimeField::new('createdAt', 'Date de demande')->hideOnForm(),
        ];
    }
}