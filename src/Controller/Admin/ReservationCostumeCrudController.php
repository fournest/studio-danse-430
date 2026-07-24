<?php

namespace App\Controller\Admin;

use App\Entity\ReservationCostume;
use App\Enum\StatutReservation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class ReservationCostumeCrudController extends AbstractCrudController
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

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

    public function configureActions(Actions $actions): Actions
    {
        // 🟢 Bouton "Valider"
        $valider = Action::new('validerReservation', 'Valider', 'fa fa-check')
            ->linkToCrudAction('validerReservation')
            ->setCssClass('btn btn-sm btn-success text-white')
            ->displayIf(static function (ReservationCostume $reservation) {
                return $reservation->getStatut() === StatutReservation::EN_ATTENTE;
            });

        // 🔴 Bouton "Refuser"
        $refuser = Action::new('refuserReservation', 'Refuser', 'fa fa-times')
            ->linkToCrudAction('refuserReservation')
            ->setCssClass('btn btn-sm btn-danger text-white')
            ->displayIf(static function (ReservationCostume $reservation) {
                return $reservation->getStatut() === StatutReservation::EN_ATTENTE;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $valider)
            ->add(Crud::PAGE_INDEX, $refuser)
            ->add(Crud::PAGE_DETAIL, $valider)
            ->add(Crud::PAGE_DETAIL, $refuser);
    }

    // -------------------------------------------------------------------------
    // LOGIQUE DES ACTIONS CUSTOM
    // -------------------------------------------------------------------------

    #[AdminRoute('/valider-reservation/{id}', name: 'admin_valider_reservation')]
    public function validerReservation(AdminContext $context): Response
    {
        /** @var ReservationCostume $reservation */
        $reservation = $context->getEntity()->getInstance();
        $reservation->setStatut(StatutReservation::VALIDEE);

        $this->entityManager->flush();

        // Envoi e-mail de confirmation à l'adhérent
        $this->envoyerEmailNotification($reservation, 'acceptée');

        $this->addFlash('success', sprintf('La réservation #%d a été VALIDÉE et le mail a été envoyé.', $reservation->getId()));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[AdminRoute('/refuser-reservation/{id}', name: 'admin_refuser_reservation')]
    public function refuserReservation(AdminContext $context): Response
    {
        /** @var ReservationCostume $reservation */
        $reservation = $context->getEntity()->getInstance();
        $reservation->setStatut(StatutReservation::REFUSEE);

        $this->entityManager->flush();

        // Envoi e-mail de refus à l'adhérent
        $this->envoyerEmailNotification($reservation, 'refusée');

        $this->addFlash('warning', sprintf('La réservation #%d a été REFUSÉE.', $reservation->getId()));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    private function envoyerEmailNotification(ReservationCostume $reservation, string $decision): void
    {
        $user = $reservation->getUser();
        if (!$user) {
            return;
        }

        $emailDestinataire = method_exists($user, 'getEmail') ? $user->getEmail() : $user->getUserIdentifier();
        $costumeNom = method_exists($reservation->getCostume(), 'getNom')
            ? $reservation->getCostume()->getNom()
            : 'Costume';

        try {
            $email = (new Email())
                ->from('no-reply@studiodanse430.fr')
                ->to($emailDestinataire)
                ->subject(sprintf('[Studio Danse 430] Votre demande de réservation a été %s', $decision))
                ->html("
                    <h2>Bonjour,</h2>
                    <p>Votre demande de réservation pour le costume <strong>{$costumeNom}</strong> (Taille : {$reservation->getTaille()}) a été <strong>{$decision}</strong> par le bureau.</p>
                    <p><strong>Prix total :</strong> {$reservation->getPrixTotal()} €</p>
                    <p>Merci de contacter le bureau si vous avez des questions.</p>
                    <br>
                    <p><em>L'équipe du Studio Danse 430</em></p>
                ");

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Empêche le plantage de l'admin en dev si le mailer n'est pas configuré
        }
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

            ChoiceField::new('modeLivraison', 'Mode de livraison')
                ->renderAsBadges([
                    'RETRAIT_LOCAUX' => 'info',
                    'POINT_RELAIS' => 'secondary',
                ]),

            MoneyField::new('prixTotal', 'Prix Total')->setCurrency('EUR')->setStoredAsCents(false),

            ChoiceField::new('statut', 'Statut')
                ->renderAsBadges([
                    StatutReservation::EN_ATTENTE->value => 'warning',
                    StatutReservation::VALIDEE->value    => 'success',
                    StatutReservation::REFUSEE->value    => 'danger',
                    StatutReservation::RESTITUEE->value   => 'info',
                    StatutReservation::ANNULEE->value    => 'secondary',
                ]),

            DateTimeField::new('createdAt', 'Date de demande')->hideOnForm(),
        ];
    }
}
