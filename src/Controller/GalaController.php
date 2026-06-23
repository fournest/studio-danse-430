<?php

namespace App\Controller;

use App\Entity\Gala;
use App\Repository\GalaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GalaController extends AbstractController
{
    #[Route('/galas', name: 'app_gala_index', methods: ['GET'])]
    public function index(GalaRepository $galaRepository): Response
    {
        return $this->render('gala/index.html.twig', [
            'galas' => $galaRepository->findUpcoming(),
        ]);
    }

    #[Route('/galas/reservation', name: 'app_gala_reservation', methods: ['GET'])]
    public function reservation(GalaRepository $galaRepository): Response
    {
        return $this->render('gala/reservation.html.twig', [
            'gala' => $galaRepository->findOneBy([], ['dateHeure' => 'DESC']),
        ]);
    }

    #[Route('/galas/{id}', name: 'app_gala_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Gala $gala): Response
    {
        return $this->render('gala/show.html.twig', [
            'gala' => $gala,
        ]);
    }
}
