<?php

namespace App\Controller\Admin;

use App\Entity\LdcDocument;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichFileType;

#[IsGranted(ClubRole::PRESIDENCE)]
class LdcDocumentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly string $ldcUploadDir,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return LdcDocument::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Déclaration LDC')
            ->setEntityLabelInPlural('Déclarations LDC (historique)')
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des Dirigeants — dépôt & historique')
            ->setPageTitle(Crud::PAGE_NEW, 'Déposer une nouvelle déclaration LDC')
            ->setPageTitle(Crud::PAGE_EDIT, fn (LdcDocument $doc) => 'Modifier la LDC '.$doc->getAnnee())
            ->setDefaultSort(['uploadedAt' => 'DESC'])
            ->setSearchFields(['annee', 'nomFichier'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $download = Action::new('downloadPdf', 'Télécharger', 'fa fa-download')
            ->linkToCrudAction('downloadDocument')
            ->displayIf(static fn (LdcDocument $doc): bool => null !== $doc->getNomFichier());

        return $actions
            ->add(Crud::PAGE_INDEX, $download)
            ->add(Crud::PAGE_DETAIL, $download);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('annee', 'Année de référence')
            ->setHelp('Ex. : 2026-2027')
            ->setFormTypeOption('constraints', [
                new NotBlank(message: 'L\'année de référence est obligatoire.'),
            ]);

        yield TextField::new('ldcFile', 'Fichier PDF')
            ->setFormType(VichFileType::class)
            ->setFormTypeOptions([
                'required' => $pageName === Crud::PAGE_NEW,
                'constraints' => [
                    new File(
                        maxSize: '15M',
                        mimeTypes: ['application/pdf', 'application/x-pdf'],
                        mimeTypesMessage: 'Seuls les fichiers PDF sont acceptés.',
                    ),
                ],
            ])
            ->onlyOnForms();

        yield TextField::new('nomFichier', 'Fichier stocké')
            ->hideOnForm()
            ->onlyOnIndex();

        yield BooleanField::new('isCurrent', 'LDC en vigueur')
            ->setHelp('Une seule déclaration peut être en vigueur. Les autres seront automatiquement archivées.')
            ->renderAsSwitch(true);

        yield DateTimeField::new('uploadedAt', 'Date de dépôt')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield AssociationField::new('uploadedBy', 'Déposé par')
            ->hideOnForm();
    }

    /**
     * @param LdcDocument $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->assignUploader($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * @param LdcDocument $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->assignUploader($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function downloadDocument(AdminContext $context): Response
    {
        /** @var LdcDocument $document */
        $document = $context->getEntity()->getInstance();

        return $this->createFileResponse($document, false);
    }

    private function assignUploader(LdcDocument $document): void
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $document->setUploadedBy($user);
        }
    }

    private function createFileResponse(LdcDocument $document, bool $inline): Response
    {
        $filename = $document->getNomFichier();
        if (null === $filename || $filename === '') {
            throw new NotFoundHttpException('Aucun fichier PDF associé à cette déclaration LDC.');
        }

        $path = $this->ldcUploadDir.'/'.$filename;
        if (!is_file($path)) {
            throw new NotFoundHttpException('Le fichier PDF de cette déclaration LDC est introuvable sur le serveur.');
        }

        $response = new BinaryFileResponse($path);
        $disposition = $inline
            ? ResponseHeaderBag::DISPOSITION_INLINE
            : ResponseHeaderBag::DISPOSITION_ATTACHMENT;

        $downloadName = sprintf('ldc-studio-danse-430-%s.pdf', preg_replace('/[^a-zA-Z0-9_-]+/', '-', $document->getAnnee() ?? 'document') ?? 'document');
        $response->setContentDisposition($disposition, $downloadName);

        return $response;
    }
}
