<?php

namespace App\Controller\Admin;

use App\Repository\BilletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SCANNER')]
final class EventScanController extends AbstractController
{
    #[Route('/admin/event/scan/{token}', name: 'admin_event_scan', methods: ['GET'], requirements: ['token' => '[0-9a-fA-F-]{36}'])]
    #[Route('/scan/validate/{token}', name: 'app_scan_validate', methods: ['GET', 'POST'], requirements: ['token' => '[0-9a-fA-F-]{36}'])]
    public function scan(
        string $token,
        Request $request,
        BilletRepository $billetRepository,
        EntityManagerInterface $em,
    ): Response {
        $wantJson = $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json')
            || $request->query->getBoolean('json')
            || $request->attributes->get('_route') === 'app_scan_validate';

        $billet = $billetRepository->findOneByToken($token);

        if (null === $billet) {
            $payload = [
                'status' => 'not_found',
                'message' => 'Billet introuvable',
                'participant' => null,
                'place' => null,
                'event' => null,
                'scannedAt' => null,
            ];

            return $wantJson
                ? new JsonResponse($payload, Response::HTTP_NOT_FOUND)
                : $this->render('admin/event/scan_result.html.twig', [
                    'status' => 'not_found',
                    'billet' => null,
                ], new Response(null, Response::HTTP_NOT_FOUND));
        }

        if ($billet->isEstValide()) {
            $payload = [
                'status' => 'already_used',
                'message' => sprintf(
                    'Billet déjà utilisé%s',
                    $billet->getScanneA() ? ' le '.$billet->getScanneA()->format('d/m/Y à H\\hi') : ''
                ),
                'participant' => $billet->getNomParticipant(),
                'place' => $billet->getNumeroPlace(),
                'event' => $billet->getEvent()?->getNom(),
                'scannedAt' => $billet->getScanneA()?->format('d/m/Y H:i'),
            ];

            return $wantJson
                ? new JsonResponse($payload)
                : $this->render('admin/event/scan_result.html.twig', [
                    'status' => 'already_used',
                    'billet' => $billet,
                ]);
        }

        $billet->marquerScanne();
        $em->flush();

        $payload = [
            'status' => 'validated',
            'message' => 'Entrée validée',
            'participant' => $billet->getNomParticipant(),
            'place' => $billet->getNumeroPlace(),
            'event' => $billet->getEvent()?->getNom(),
            'scannedAt' => $billet->getScanneA()?->format('d/m/Y H:i'),
        ];

        return $wantJson
            ? new JsonResponse($payload)
            : $this->render('admin/event/scan_result.html.twig', [
                'status' => 'validated',
                'billet' => $billet,
            ]);
    }
}
