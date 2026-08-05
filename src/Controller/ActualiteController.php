<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActualiteController extends AbstractController
{
    #[Route('/actualites', name: 'app_actualite_index', methods: ['GET'])]
    public function index(ActualiteRepository $actualiteRepository): Response
    {
        return $this->render('actualite/index.html.twig', [
            'actualites' => $actualiteRepository->findAllPublished(),
        ]);
    }

    #[Route('/actualites/{slug}', name: 'app_actualite_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug, ActualiteRepository $actualiteRepository): Response
    {
        $actualite = $actualiteRepository->findOnePublishedBySlug($slug);
        if (null === $actualite) {
            throw $this->createNotFoundException('Cette actualité est introuvable ou non publiée.');
        }

        return $this->render('actualite/show.html.twig', [
            'actualite' => $actualite,
        ]);
    }
}
