<?php

namespace App\Controller\Admin;

use App\Entity\Cours;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class CoursCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Cours::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nom');
        yield TextField::new('jour');
        yield TimeField::new('heure');
        yield TextField::new('professeur', 'Professeur');
        yield ChoiceField::new('dureeMinutes', 'Durée')
            ->setChoices([
                '1h00' => 60,
                '1h15' => 75,
                '1h30' => 90,
            ]);
        yield NumberField::new('tarif', 'Tarif (€)')
            ->setNumDecimals(2)
            ->setHelp('Grille tarifaire saison — utilisée par le calculateur de cotisations.');
        yield IntegerField::new('anneeNaissanceMin', 'Année naissance min')
            ->setHelp('Ex. 2008 pour Enfants/Ados. Laisser vide = pas de borne.')
            ->hideOnIndex();
        yield IntegerField::new('anneeNaissanceMax', 'Année naissance max')
            ->setHelp('Ex. 2022 pour Enfants/Ados ; 2007 pour Adultes.')
            ->hideOnIndex();
        yield IntegerField::new('capaciteMax')->setLabel('Capacité max');
        yield UrlField::new('whatsappGroupLink')
            ->setLabel('Lien groupe WhatsApp')
            ->hideOnIndex();
    }
}
