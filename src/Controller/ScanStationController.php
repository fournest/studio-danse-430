<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SCANNER')]
final class ScanStationController extends AbstractController
{
    #[Route('/scan', name: 'app_scan_station', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $event = $eventRepository->findCurrentOrNext();

        return $this->render('scan/station.html.twig', [
            'event' => $event,
        ]);
    }
}
