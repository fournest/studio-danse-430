<?php

namespace App\Controller\Admin;

use App\Entity\Cours;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
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
        yield TextField::new('professeur');
        yield IntegerField::new('capaciteMax')->setLabel('Capacité max');
        yield UrlField::new('whatsappGroupLink')
            ->setLabel('Lien groupe WhatsApp')
            ->hideOnIndex();
    }
}
