<?php

namespace App\Controller\Admin;

use App\Entity\Gala;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GalaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Gala::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nom');
        yield DateTimeField::new('dateHeure')->setLabel('Date et heure');
        yield AssociationField::new('salle');
        yield IntegerField::new('placesDisponibles')->setLabel('Places disponibles');
        yield TextField::new('billetwebEventId')
            ->setLabel('ID événement Billetweb')
            ->hideOnIndex();
    }
}
