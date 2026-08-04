<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Repository\CoursRepository;
use App\Service\CotisationCalculatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CoursController extends AbstractController
{
    #[Route('/cours', name: 'app_cours_index', methods: ['GET'])]
    public function index(CoursRepository $coursRepository): Response
    {
        $saison = CotisationCalculatorService::SAISON_COURANTE;
        $coursParJour = $coursRepository->findGroupedByJour();
        $totalCours = array_sum(array_map('count', $coursParJour));

        return $this->render('cours/index.html.twig', [
            'coursParJour' => $coursParJour,
            'totalCours' => $totalCours,
            'saison' => $saison,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Cours $cours): Response
    {
        return $this->render('cours/show.html.twig', [
            'cours' => $cours,
            'saison' => CotisationCalculatorService::SAISON_COURANTE,
        ]);
    }
}
