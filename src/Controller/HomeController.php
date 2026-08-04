<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Repository\ActualiteRepository;
use App\Repository\AlbumRepository;
use App\Repository\CoursRepository;
use App\Repository\SponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        CoursRepository $coursRepository,
        SponsorRepository $sponsorRepository,
        ActualiteRepository $actualiteRepository,
        AlbumRepository $albumRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'disciplines' => $this->groupCoursByDiscipline($coursRepository->findAllOrdered()),
            'sponsors' => $sponsorRepository->findAll(),
            'actualites' => $actualiteRepository->findLatest(5),
            'albums' => $albumRepository->findBy([], ['dateEvenement' => 'DESC']),
        ]);
    }

    /**
     * Regroupe les créneaux par nom principal de discipline (sans n° de groupe).
     *
     * @param list<Cours> $cours
     *
     * @return list<array{
     *     nom: string,
     *     creaneaux: list<Cours>,
     *     professeurs: list<string>,
     *     tarif: float|null,
     *     capaciteMax: int,
     *     showId: int|null
     * }>
     */
    private function groupCoursByDiscipline(array $cours): array
    {
        /** @var array<string, array{nom: string, creaneaux: list<Cours>, professeurs: array<string, true>, tarif: float|null, capaciteMax: int, showId: int|null}> $groups */
        $groups = [];

        foreach ($cours as $c) {
            $nom = trim($c->getNom());
            if ($nom === '') {
                continue;
            }

            if (!isset($groups[$nom])) {
                $groups[$nom] = [
                    'nom' => $nom,
                    'creaneaux' => [],
                    'professeurs' => [],
                    'tarif' => null,
                    'capaciteMax' => $c->getCapaciteMax(),
                    'showId' => $c->getId(),
                ];
            }

            $groups[$nom]['creaneaux'][] = $c;
            $groups[$nom]['capaciteMax'] = max($groups[$nom]['capaciteMax'], $c->getCapaciteMax());

            $tarif = (float) $c->getTarif();
            if ($tarif > 0 && (null === $groups[$nom]['tarif'] || $tarif < $groups[$nom]['tarif'])) {
                $groups[$nom]['tarif'] = $tarif;
            }

            foreach ($c->getProfesseursNoms() as $profNom) {
                $parts = preg_split('/\s+/u', $profNom, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $prenom = $parts[0] ?? $profNom;
                if ($prenom !== '') {
                    $groups[$nom]['professeurs'][$prenom] = true;
                }
            }
        }

        $disciplines = [];
        foreach ($groups as $group) {
            $disciplines[] = [
                'nom' => $group['nom'],
                'creaneaux' => $group['creaneaux'],
                'professeurs' => array_keys($group['professeurs']),
                'tarif' => $group['tarif'],
                'capaciteMax' => $group['capaciteMax'],
                'showId' => $group['showId'],
            ];
        }

        return $disciplines;
    }
}
