<?php

namespace App\Controller\Admin;

use App\Entity\Costume;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class CostumeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Costume::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nom', 'Nom du costume');
        yield TextField::new('taille', 'Taille(s) / Âge')
            ->setHelp('Une taille (« M »), une liste (« S, M, L ») ou un intervalle (« S à L »). Affiché en menu déroulant à la réservation.');
        yield TextField::new('theme', 'Thème')
            ->setHelp('Ex. Cabaret, Disco, Comédie musicale…')
            ->hideOnIndex();
        yield ChoiceField::new('genre', 'Genre')
            ->setChoices([
                'Homme' => 'Homme',
                'Femme' => 'Femme',
                'Enfant' => 'Enfant',
            ])
            ->setRequired(false)
            ->renderAsBadges();

        yield IntegerField::new('prix', 'Prix location (€/WE)');
        yield MoneyField::new('tarifLocationHorsGala', 'Tarif hors gala (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setHelp('Si renseigné, prioritaire sur le prix ci-dessus pour la location hors gala.')
            ->hideOnIndex();

        yield IntegerField::new('quantite', 'Nombre d\'exemplaires');
        yield BooleanField::new('disponibleHorsGala', 'Dispo. hors gala')
            ->renderAsSwitch(true);

        yield TextEditorField::new('description', 'Consignes & Accessoires')
            ->hideOnIndex();

        yield ImageField::new('photo', 'Photo du costume')
            ->setBasePath('uploads/costumes')
            ->setUploadDir('public/uploads/costumes')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
    }
}
