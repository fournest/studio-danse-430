<?php

namespace App\Controller;

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
    // 2. On injecte le SponsorRepository en paramètre de la fonction
    public function index(
        CoursRepository $coursRepository,
        SponsorRepository $sponsorRepository,
        ActualiteRepository $actualiteRepository,
        AlbumRepository $albumRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'cours' => $coursRepository->findAllOrdered(),
            'sponsors' => $sponsorRepository->findAll(),
            'actualites' => $actualiteRepository->findLatest(5),
            'albums' => $albumRepository->findBy([], ['dateEvenement' => 'DESC']),
        ]);
    }
}
