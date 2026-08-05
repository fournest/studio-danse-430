<?php

namespace App\Controller\Admin;

use App\Entity\PageLegale;
use App\Security\ClubRole;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(ClubRole::BUREAU)]
class PageLegaleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PageLegale::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page légale')
            ->setEntityLabelInPlural('Pages légales')
            ->setPageTitle(Crud::PAGE_INDEX, 'Pages légales du site')
            ->setPageTitle(Crud::PAGE_EDIT, fn (PageLegale $page) => 'Modifier : '.$page->getTitre())
            ->setDefaultSort(['titre' => 'ASC'])
            ->setSearchFields(['titre', 'slug', 'contenu'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $preview = Action::new('preview', 'Voir sur le site', 'fa fa-external-link')
            ->linkToUrl(fn (PageLegale $page): string => $this->generateUrl('app_legales_show', ['slug' => $page->getSlug()]))
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener'])
            ->addCssClass('btn btn-secondary');

        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_EDIT, $preview);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('titre', 'Titre');

        yield TextField::new('slug', 'Slug URL')
            ->onlyOnIndex()
            ->setHelp('URLs fixes : mentions-legales, politique-de-confidentialite, cgu — ne pas modifier.');

        yield TextEditorField::new('contenu', 'Contenu')
            ->hideOnIndex()
            ->setHelp('Texte affiché sur la page publique. Pensez à relire après chaque AG ou changement de bureau.');

        yield DateTimeField::new('updatedAt', 'Dernière mise à jour')
            ->setFormTypeOption('disabled', true)
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    /**
     * @param PageLegale $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setUpdatedAt(new \DateTimeImmutable());
        parent::updateEntity($entityManager, $entityInstance);
    }
}
