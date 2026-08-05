<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Security\ClubRole;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(ClubRole::BUREAU)]
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['email' => 'ASC'])
            ->overrideTemplate('crud/edit', 'admin/user/edit.html.twig')
            ->overrideTemplate('crud/new', 'admin/user/new.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        $canAssignPresidence = $this->isGranted(ClubRole::PRESIDENCE);
        $roleChoices = $this->buildRoleChoices($canAssignPresidence);

        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email');
        yield TextField::new('telephone')->setLabel('Téléphone');

        yield ChoiceField::new('primaryClubRole', 'Rôle au club')
            ->setChoices($roleChoices)
            ->renderAsBadges()
            ->setHelp('Sélectionnez le statut officiel du membre pour la saison en cours. Les rôles Présidence ne sont modifiables que par la Présidence.')
            ->formatValue(static fn (?string $value): string => ClubRole::label($value ?? ClubRole::USER))
            ->setPermission(ClubRole::BUREAU);

        yield AssociationField::new('foyer', 'Foyer / Famille')
            ->setHelp('Le dossier familial associé à ce compte utilisateur.');

        yield BooleanField::new('isVerified', 'E-mail vérifié');
        yield BooleanField::new('isActif', 'Compte actif');
    }

    public function configureActions(Actions $actions): Actions
    {
        $ban = Action::new('ban', 'Bannir / Débannir', 'fa fa-ban')
            ->linkToCrudAction('banUser')
            ->addCssClass('text-red-500');

        $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
            return $action->setHtmlAttributes([
                'data-confirm' => 'Êtes-vous sûr de vouloir supprimer cet utilisateur définitivement ?',
            ]);
        });

        return $actions->add(Crud::PAGE_INDEX, $ban);
    }

    /**
     * @param User $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->assertRoleAssignmentAllowed($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
        $this->flashLdcReminderIfNeeded($entityInstance, null);
    }

    /**
     * @param User $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $previousRole = ClubRole::extractPrimaryRole(
            $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['roles'] ?? []
        );

        $this->assertRoleAssignmentAllowed($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
        $this->flashLdcReminderIfNeeded($entityInstance, $previousRole);
    }

    public function banUser(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $user->setIsActif(!$user->isActif());
        $entityManager->flush();

        $this->addFlash(
            'success',
            $user->isActif()
                ? sprintf('Le compte %s a été réactivé.', $user->getEmail())
                : sprintf('Le compte %s a été désactivé.', $user->getEmail())
        );

        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return new RedirectResponse($url);
    }

    /**
     * @return array<string, string>
     */
    private function buildRoleChoices(bool $canAssignPresidence): array
    {
        $choices = ClubRole::assignableChoices($canAssignPresidence);

        $entity = $this->getContext()?->getEntity()?->getInstance();
        if ($entity instanceof User && \in_array(ClubRole::BUREAU_LEGACY, $entity->getRoles(), true)) {
            $choices = ['Membre du bureau (legacy — à préciser)' => ClubRole::BUREAU_LEGACY] + $choices;
        }

        return $choices;
    }

    private function assertRoleAssignmentAllowed(User $user): void
    {
        $role = $user->getPrimaryClubRole();

        if (ClubRole::isPresidenceMember($role) && !$this->isGranted(ClubRole::PRESIDENCE)) {
            throw $this->createAccessDeniedException(
                'Seule la Présidence peut attribuer le rôle de Président(e) ou Vice-Président(e).'
            );
        }
    }

    private function flashLdcReminderIfNeeded(User $user, ?string $previousRole): void
    {
        $newRole = $user->getPrimaryClubRole();

        if (!ClubRole::isBureauMember($newRole)) {
            return;
        }

        if (null !== $previousRole && $previousRole === $newRole) {
            return;
        }

        $this->addFlash(
            'warning',
            'Rappel LDC : toute modification de la composition du Bureau doit faire l\'objet d\'une déclaration officielle en préfecture. '
            .'Avez-vous mis à jour et déposé le document LDC officiel ? '
            .'La Présidence peut déposer la nouvelle version depuis le menu « Déclarations LDC ».'
        );
    }
}
