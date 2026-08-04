<?php

namespace App\Controller\Admin;

use App\Entity\Danseur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_BUREAU')]
class CertificatController extends AbstractController
{
    #[Route('/admin/danseur/{id}/certificat', name: 'app_admin_danseur_certificat_download', methods: ['GET'])]
    public function download(Danseur $danseur): Response
    {
        $filename = $danseur->getCertificatFilename();
        if (!$filename) {
            $this->addFlash('danger', 'Aucun certificat médical pour ce danseur.');

            return $this->redirectToRoute('admin');
        }

        $path = $this->getParameter('kernel.project_dir') . '/var/uploads/certificats/' . $filename;
        if (!is_file($path)) {
            $this->addFlash('danger', 'Le fichier du certificat est introuvable sur le serveur.');

            return $this->redirectToRoute('admin');
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'pdf';
        $downloadName = sprintf(
            'Certificat_%s_%s.%s',
            $this->sanitizeFilenamePart($danseur->getNom() ?? 'NOM'),
            $this->sanitizeFilenamePart($danseur->getPrenom() ?? 'Prenom'),
            $extension
        );

        return $this->file($path, $downloadName);
    }

    private function sanitizeFilenamePart(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value) ?? $value;

        return trim($value, '_') ?: 'X';
    }
}
