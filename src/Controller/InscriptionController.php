<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class InscriptionController extends AbstractController
{
    /**
     * Ancienne URL conservée pour compatibilité : redirige vers le tunnel foyer.
     */
    #[Route('/inscription', name: 'app_inscription', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_foyer_inscription_cours');
    }
}
