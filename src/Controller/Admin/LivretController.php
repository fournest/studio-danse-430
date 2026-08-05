<?php

namespace App\Controller\Admin;

use App\Service\LivretRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Rétrocompatibilité : redirige les anciennes URLs /admin/livrets/{slug} vers /admin/doc/voir/{type}.
 */
#[AdminRoute('/livrets', name: 'livrets')]
final class LivretController extends AbstractController
{
    public function __construct(
        private readonly LivretRegistry $livretRegistry,
    ) {
    }

    #[AdminRoute('/{slug}', name: 'show', options: ['methods' => ['GET']])]
    public function show(string $slug): Response
    {
        $livret = $this->livretRegistry->get($slug);
        if (null === $livret) {
            throw new NotFoundHttpException('Ce livret n\'existe pas.');
        }

        return $this->redirectToRoute('admin_doc_voir', ['type' => $livret['type']]);
    }

    #[AdminRoute('/{slug}/download', name: 'download', options: ['methods' => ['GET']])]
    public function download(string $slug): Response
    {
        $livret = $this->livretRegistry->get($slug);
        if (null === $livret) {
            throw new NotFoundHttpException('Ce livret n\'existe pas.');
        }

        return $this->redirectToRoute('admin_doc_pdf', ['type' => $livret['type']]);
    }
}
