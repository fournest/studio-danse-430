<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Enum\EventType;
use App\Enum\SeatingType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield DateTimeField::new('dateHeure', 'Date et heure')
            ->setFormat('dd/MM/yyyy HH:mm');
        yield AssociationField::new('salle');
        yield IntegerField::new('placesDisponibles', 'Places disponibles');
        yield ChoiceField::new('type', 'Type')
            ->setChoices($this->enumChoices(EventType::cases()));
        yield ChoiceField::new('modePlacement', 'Mode de placement')
            ->setChoices($this->enumChoices(SeatingType::cases()));
        yield TextareaField::new('consignesStaff', 'Consignes staff / accueil')
            ->setHelp('Brief visible sur la station /scan (ex. : distribuer le programme, orienter les VIP…).')
            ->hideOnIndex();
        yield AssociationField::new('benevoles', 'Bénévoles assignés')
            ->setFormTypeOption('by_reference', false)
            ->autocomplete()
            ->setHelp('Utilisateurs désignés pour cet événement (scanners / accueil).')
            ->hideOnIndex();
    }

    /**
     * @param list<\BackedEnum> $cases
     *
     * @return array<string, \BackedEnum>
     */
    private function enumChoices(array $cases): array
    {
        $choices = [];
        foreach ($cases as $case) {
            $label = method_exists($case, 'getLabel') ? $case->getLabel() : (string) $case->value;
            $choices[$label] = $case;
        }

        return $choices;
    }
}
