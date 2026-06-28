<?php
namespace App\Controller;

use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CoursRepository $coursRepository): Response
    {
        // Sponsors de test en dur (remplace par tes vrais sponsors plus tard)
        $sponsors = [
            ['nom' => 'Mairie', 'logo' => '/images/sponsors/mairie.jpg', 'lien' => '#'],
            ['nom' => 'Crédit Agricole', 'logo' => '/images/sponsors/ca.jpg', 'lien' => '#'],
            ['nom' => 'Intermarché', 'logo' => '/images/sponsors/intermarche.jpg', 'lien' => '#'],
            ['nom' => 'Studio Danse 430', 'logo' => '/images/logo.studio-danse-430.jpg', 'lien' => '#'],
        ];

        return $this->render('home/index.html.twig', [
            'cours' => $coursRepository->findAllOrdered(),
            'sponsors' => $sponsors, // Passe les sponsors en dur
        ]);
    }
}