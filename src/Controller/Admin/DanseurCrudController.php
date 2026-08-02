<?php

namespace App\Controller\Admin;

use App\Entity\Danseur;
use App\Enum\StatutSante;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DanseurCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Danseur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Danseur')
            ->setEntityLabelInPlural('Danseurs')
            ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $telecharger = Action::new('telechargerCertificat', 'Certificat', 'fa fa-file-medical')
            ->linkToCrudAction('telechargerCertificat')
            ->setCssClass('btn btn-sm btn-secondary')
            ->displayIf(static fn (Danseur $d) => null !== $d->getCertificatFilename());

        $valider = Action::new('validerSante', 'Valider le justificatif santé', 'fa fa-check')
            ->linkToCrudAction('validerSante')
            ->setCssClass('btn btn-sm btn-success')
            ->displayIf(static fn (Danseur $d) => $d->getStatutSante() !== StatutSante::VALIDE_BUREAU
                && $d->hasJustificatifSanteComplet());

        return $actions
            ->add(Crud::PAGE_INDEX, $telecharger)
            ->add(Crud::PAGE_INDEX, $valider)
            ->add(Crud::PAGE_DETAIL, $telecharger)
            ->add(Crud::PAGE_DETAIL, $valider);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom', 'Nom');
        yield DateField::new('dateNaissance', 'Date de naissance');

        yield ChoiceField::new('statutSante', 'Statut santé')
            ->setChoices($this->enumChoices(StatutSante::cases()))
            ->formatValue(static fn ($value, ?Danseur $entity) => $entity?->getStatutSante()->getLabel() ?? '')
            ->renderAsBadges([
                StatutSante::EN_ATTENTE->value => 'warning',
                StatutSante::QS_SPORT_VALIDE->value => 'info',
                StatutSante::CERTIFICAT_FOURNI->value => 'primary',
                StatutSante::VALIDE_BUREAU->value => 'success',
            ]);

        yield BooleanField::new('attestationQsSportValide', 'Attestation QS-Sport')
            ->hideOnIndex();
        yield DateTimeField::new('dateSignatureQsSport', 'Signature QS-Sport')
            ->hideOnIndex();
        yield TextField::new('certificatFilename', 'Fichier certificat')
            ->hideOnIndex()
            ->setDisabled();
        yield TextareaField::new('remarqueSante', 'Remarques santé')
            ->hideOnIndex();

        yield AssociationField::new('foyer', 'Foyer / Famille')
            ->setHelp('Le foyer familial auquel ce danseur est rattaché.');

        yield AssociationField::new('cours', 'Cours suivis')
            ->hideOnIndex();

        yield TextField::new('parent2NomComplet', '2ᵉ Parent / Urgence')
            ->onlyOnIndex()
            ->formatValue(function ($value, Danseur $entity) {
                if ($entity->getParent2Nom() || $entity->getParent2Prenom()) {
                    return sprintf('⚠️ %s (Spécifique)', $entity->getParent2NomComplet());
                }

                return $value ?: 'Aucun 2ᵉ parent';
            });

        yield TextField::new('parent2Prenom', 'Prénom (2ᵉ Parent Spécifique)')
            ->onlyOnForms()
            ->setHelp('À remplir UNIQUEMENT si le 2ᵉ parent de cet enfant est différent de celui du Foyer.');
        yield TextField::new('parent2Nom', 'Nom (2ᵉ Parent Spécifique)')
            ->onlyOnForms();
        yield TelephoneField::new('parent2Telephone', 'Téléphone (2ᵉ Parent Spécifique)')
            ->onlyOnForms();
        yield EmailField::new('parent2Email', 'E-mail (2ᵉ Parent Spécifique)')
            ->onlyOnForms();
    }

    #[AdminRoute('/{entityId}/telecharger-certificat', name: 'telecharger_certificat')]
    public function telechargerCertificat(AdminContext $context): Response
    {
        /** @var Danseur $danseur */
        $danseur = $context->getEntity()->getInstance();
        $filename = $danseur->getCertificatFilename();
        if (!$filename) {
            throw $this->createNotFoundException('Aucun certificat déposé.');
        }

        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/certificats/' . $filename;
        if (!is_file($path)) {
            throw $this->createNotFoundException('Fichier introuvable sur le serveur.');
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE . '; filename="' . $filename . '"',
        ]);
    }

    #[AdminRoute('/{entityId}/valider-sante', name: 'valider_sante')]
    public function validerSante(AdminContext $context): Response
    {
        /** @var Danseur $danseur */
        $danseur = $context->getEntity()->getInstance();
        $danseur->setStatutSante(StatutSante::VALIDE_BUREAU);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Justificatif santé de %s validé par le bureau.', $danseur));

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    /**
     * @param \BackedEnum[] $cases
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
