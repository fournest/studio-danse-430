<?php

namespace App\Controller\Admin;

use App\Repository\LdcDocumentRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_BUREAU')]
#[AdminRoute('/ldc', name: 'ldc')]
final class LdcDocumentController extends AbstractController
{
    public function __construct(
        private readonly LdcDocumentRepository $ldcDocumentRepository,
        private readonly string $ldcUploadDir,
        private readonly string $ldcLegacyDocumentPath,
    ) {
    }

    #[AdminRoute('/telecharger', name: 'download', options: ['methods' => ['GET']])]
    public function download(Request $request): Response
    {
        $inline = $request->query->getBoolean('inline');
        $path = $this->resolveCurrentFilePath();

        if (null === $path) {
            throw new NotFoundHttpException(
                'Aucune déclaration LDC en vigueur n\'a encore été déposée. La Présidence peut en ajouter une depuis le menu « Déclarations LDC ».'
            );
        }

        $response = new BinaryFileResponse($path);
        $disposition = $inline
            ? ResponseHeaderBag::DISPOSITION_INLINE
            : ResponseHeaderBag::DISPOSITION_ATTACHMENT;

        $response->setContentDisposition($disposition, 'ldc-studio-danse-430.pdf');

        return $response;
    }

    private function resolveCurrentFilePath(): ?string
    {
        $current = $this->ldcDocumentRepository->findCurrent();
        if (null !== $current && null !== $current->getNomFichier()) {
            $path = $this->ldcUploadDir.'/'.$current->getNomFichier();
            if (is_file($path)) {
                return $path;
            }
        }

        if (is_file($this->ldcLegacyDocumentPath)) {
            return $this->ldcLegacyDocumentPath;
        }

        return null;
    }
}
