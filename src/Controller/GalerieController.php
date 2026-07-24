<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GalerieController extends AbstractController
{
    #[Route('/galerie', name: 'app_galerie_index')]
    public function index(AlbumRepository $albumRepository): Response
    {
        return $this->render('galerie/index.html.twig', [
            'albums' => $albumRepository->findBy([], ['dateEvenement' => 'DESC']),
        ]);
    }

    #[Route('/galerie/{id}', name: 'app_galerie_show')]
    public function show(Album $album): Response
    {
        return $this->render('galerie/show.html.twig', [
            'album' => $album,
        ]);
    }
}