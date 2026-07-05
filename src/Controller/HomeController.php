<?php
namespace App\Controller;

use App\Repository\CoursRepository;
use App\Repository\SponsorRepository; // 1. On importe le dépôt des Sponsors
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    // 2. On injecte le SponsorRepository en paramètre de la fonction
    public function index(CoursRepository $coursRepository, SponsorRepository $sponsorRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'cours' => $coursRepository->findAllOrdered(),
            // 3. On récupère les vrais sponsors de la base de données
            'sponsors' => $sponsorRepository->findAll(), 
        ]);
    }
}