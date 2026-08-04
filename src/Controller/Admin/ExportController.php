<?php

namespace App\Controller\Admin;

use App\Entity\Cours;
use App\Entity\Danseur;
use App\Repository\DanseurRepository;
use App\Service\CotisationCalculatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PROF')]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly DanseurRepository $danseurRepository,
    ) {
    }

    #[Route('/admin/export/adherents', name: 'app_admin_export_adherents', methods: ['GET'])]
    #[IsGranted('ROLE_BUREAU')]
    public function exportAdherents(): Response
    {
        $danseurs = $this->danseurRepository->findAllForExport();
        $saison = CotisationCalculatorService::SAISON_COURANTE;

        $rows = [];
        $rows[] = [
            'ID Foyer',
            'Nom Foyer',
            'Nom Danseur',
            'Prénom Danseur',
            'Date de naissance',
            'Âge',
            'Téléphone Parent 1',
            'Téléphone Parent 2',
            'Email Parent 1',
            'Email Parent 2',
            'Cours inscrits',
            'Statut Santé',
            'Remarques Médicales',
        ];

        foreach ($danseurs as $danseur) {
            $foyer = $danseur->getFoyer();
            $parent1 = $foyer?->getUser();
            $tel2 = $danseur->getParent2TelephoneEffectif();
            $email2 = $danseur->getParent2EmailEffectif();

            $rows[] = [
                (string) ($foyer?->getId() ?? ''),
                (string) ($foyer?->getNom() ?? ''),
                (string) ($danseur->getNom() ?? ''),
                (string) ($danseur->getPrenom() ?? ''),
                $danseur->getDateNaissance()?->format('d/m/Y') ?? '',
                (string) ($this->computeAge($danseur) ?? ''),
                (string) ($parent1?->getTelephone() ?? ''),
                (string) ($tel2 ?? ''),
                (string) ($parent1?->getEmail() ?? ''),
                (string) ($email2 ?? ''),
                $this->formatCoursList($danseur, $saison),
                $danseur->getStatutSante()->getLabel(),
                (string) ($danseur->getRemarqueSante() ?? ''),
            ];
        }

        return $this->csvResponse($rows, 'Export_Adherents_Studio_Danse_430.csv');
    }

    #[Route('/admin/export/cours/{id}', name: 'app_admin_export_cours', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportCours(Cours $cours): Response
    {
        $saison = CotisationCalculatorService::SAISON_COURANTE;
        $danseurs = $this->danseurRepository->findForCoursExport($cours, $saison);

        $rows = [];
        $rows[] = [
            'Nom',
            'Prénom',
            'Date de naissance',
            'Contacts d’urgence (Parents)',
            'Remarques médicales',
            'Statut santé',
        ];

        foreach ($danseurs as $danseur) {
            $rows[] = [
                (string) ($danseur->getNom() ?? ''),
                (string) ($danseur->getPrenom() ?? ''),
                $danseur->getDateNaissance()?->format('d/m/Y') ?? '',
                $this->formatContactsUrgence($danseur),
                (string) ($danseur->getRemarqueSante() ?? ''),
                $danseur->getStatutSante()->getLabel(),
            ];
        }

        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', $cours->getNom()) ?: 'Cours';
        $filename = sprintf('Liste_Eleves_%s.csv', $slug);

        return $this->csvResponse($rows, $filename);
    }

    /**
     * @param list<list<string>> $rows
     */
    private function csvResponse(array $rows, string $filename): Response
    {
        $handle = fopen('php://temp', 'r+');
        if (false === $handle) {
            throw new \RuntimeException('Impossible de créer le flux CSV.');
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        $content = "\xEF\xBB\xBF" . $csv;

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function computeAge(Danseur $danseur): ?int
    {
        $naissance = $danseur->getDateNaissance();
        if (null === $naissance) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($naissance)
            ->diff(new \DateTimeImmutable('today'))
            ->y;
    }

    private function formatCoursList(Danseur $danseur, string $saison): string
    {
        $noms = [];
        foreach ($danseur->getInscriptions() as $inscription) {
            if ($inscription->getSaison() !== $saison) {
                continue;
            }
            $nom = $inscription->getCours()?->getNom();
            if ($nom) {
                $noms[$nom] = $nom;
            }
        }

        if ($noms === []) {
            foreach ($danseur->getCours() as $cours) {
                $noms[$cours->getNom()] = $cours->getNom();
            }
        }

        return implode(', ', array_values($noms));
    }

    private function formatContactsUrgence(Danseur $danseur): string
    {
        $foyer = $danseur->getFoyer();
        $parts = [];

        $parent1 = $foyer?->getUser();
        if ($parent1) {
            $parts[] = trim(sprintf(
                'P1: %s / %s',
                $parent1->getTelephone() ?? '—',
                $parent1->getEmail() ?? '—'
            ));
        }

        $tel2 = $danseur->getParent2TelephoneEffectif();
        $email2 = $danseur->getParent2EmailEffectif();
        $nom2 = $danseur->getParent2NomComplet();
        if ($tel2 || $email2 || $nom2) {
            $parts[] = trim(sprintf(
                'P2%s: %s / %s',
                $nom2 ? ' (' . $nom2 . ')' : '',
                $tel2 ?? '—',
                $email2 ?? '—'
            ));
        }

        if ($foyer?->getContactUrgence()) {
            $parts[] = 'Urgence: ' . $foyer->getContactUrgence();
        }

        return implode(' | ', $parts);
    }
}
