<?php

namespace App\Controller\Admin;

use App\Entity\Inscription;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class InscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Inscription::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('danseur');
        yield AssociationField::new('cours');
        yield TextField::new('saison');

        yield ChoiceField::new('statutDossier')
            ->setChoices($this->enumChoices(StatutDossier::cases()))
            ->renderAsBadges([
                StatutDossier::EN_ATTENTE->value => 'warning',
                StatutDossier::INCOMPLET->value => 'danger',
                StatutDossier::VALIDE->value => 'success',
            ]);

        yield ChoiceField::new('statutPaiement')
            ->setChoices($this->enumChoices(StatutPaiement::cases()))
            ->renderAsBadges([
                StatutPaiement::NON_PAYE->value => 'danger',
                StatutPaiement::PARTIEL->value => 'warning',
                StatutPaiement::SOLDE->value => 'success',
            ]);

        yield TextField::new('modePaiement')->hideOnIndex();
        yield TextField::new('certificatMedical')->hideOnIndex();
        yield TextField::new('helloAssoPaymentId')
            ->setLabel('ID paiement HelloAsso')
            ->hideOnIndex();
    }

    /**
     * Construit un tableau [label => enum] exploitable par ChoiceField.
     *
     * @param \BackedEnum[] $cases
     * @return array<string, \BackedEnum>
     */
    private function enumChoices(array $cases): array
    {
        $choices = [];
        foreach ($cases as $case) {
            $choices[(string) $case->value] = $case;
        }

        return $choices;
    }
}
