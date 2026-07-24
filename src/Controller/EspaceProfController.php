<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PROF')]
class EspaceProfController extends AbstractController
{
    #[Route('/espace-prof', name: 'app_espace_prof')]
    public function index(CoursRepository $coursRepository): Response
    {
        /** @var \App\Entity\User $prof */
        $prof = $this->getUser();

        // Récupère uniquement les cours dispensés par ce prof
        $mesCours = $coursRepository->findBy(['professeur' => $prof->getEmail()]);

        return $this->render('espace_prof/index.html.twig', [
            'cours' => $mesCours,
        ]);
    }
}