<?php

namespace App\Controller;

use App\Repository\CostumeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CostumeController extends AbstractController
{
    #[Route('/location-costumes', name: 'app_costume_index', methods: ['GET'])]
    public function index(CostumeRepository $costumeRepository): Response
    {
        return $this->render('costume/index.html.twig', [
            'costumes' => $costumeRepository->findAll(),
        ]);
    }
}