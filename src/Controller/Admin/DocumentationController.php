<?php

namespace App\Controller\Admin;

use App\Service\LivretPdfGenerator;
use App\Service\LivretRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AdminRoute('/doc', name: 'doc')]
final class DocumentationController extends AbstractController
{
    public function __construct(
        private readonly LivretRegistry $livretRegistry,
        private readonly LivretPdfGenerator $livretPdfGenerator,
    ) {
    }

    #[AdminRoute('/voir/{type}', name: 'voir', options: ['methods' => ['GET']])]
    public function voir(string $type): Response
    {
        $livret = $this->livretRegistry->get($type);
        if (null === $livret) {
            throw new NotFoundHttpException('Ce livret n\'existe pas.');
        }

        $this->denyAccessUnlessGranted($livret['required_role']);

        return $this->render($livret['template_screen'], [
            'livret' => $livret,
            'generated_at' => new \DateTimeImmutable(),
            'pdf_download_url' => $this->generateUrl('admin_doc_pdf', [
                'type' => $livret['type'],
            ]),
            'pdf_inline_url' => $this->generateUrl('admin_doc_pdf', [
                'type' => $livret['type'],
                'inline' => 1,
            ]),
        ]);
    }

    #[AdminRoute('/pdf/{type}', name: 'pdf', options: ['methods' => ['GET']])]
    public function pdf(string $type, Request $request): Response
    {
        $livret = $this->livretRegistry->get($type);
        if (null === $livret) {
            throw new NotFoundHttpException('Ce livret n\'existe pas.');
        }

        $this->denyAccessUnlessGranted($livret['required_role']);

        $generatedAt = new \DateTimeImmutable();
        $pdfContent = $this->livretPdfGenerator->generate($livret['template_pdf'], [
            'livret' => $livret,
            'generated_at' => $generatedAt,
        ]);

        $inline = $request->query->getBoolean('inline');
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $disposition = $inline
            ? ResponseHeaderBag::DISPOSITION_INLINE
            : ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition($disposition, $livret['pdf_filename'])
        );

        return $response;
    }
}
