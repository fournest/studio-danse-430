<?php

namespace App\Controller;

use App\Entity\PageLegale;
use App\Repository\PageLegaleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class LegalesController extends AbstractController
{
    #[Route('/legales/{slug}', name: 'app_legales_show', methods: ['GET'])]
    public function show(string $slug, PageLegaleRepository $repository): Response
    {
        $page = $repository->findOneBy(['slug' => $slug]);
        if (!$page instanceof PageLegale) {
            throw new NotFoundHttpException('Cette page légale n\'existe pas.');
        }

        return $this->render('legales/show.html.twig', [
            'page' => $page,
        ]);
    }
}
