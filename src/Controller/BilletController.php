<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BilletRepository;
use App\Service\BilletQrCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class BilletController extends AbstractController
{
    #[Route('/mes-billets', name: 'app_billets_index', methods: ['GET'])]
    public function index(BilletRepository $billetRepository, BilletQrCodeService $qrCodeService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $billets = $billetRepository->findByUserOrdered($user);

        $qrCodes = [];
        foreach ($billets as $billet) {
            $qrCodes[$billet->getId()] = $qrCodeService->pngDataUri($billet->getToken());
        }

        return $this->render('account/billets.html.twig', [
            'billets' => $billets,
            'qrCodes' => $qrCodes,
        ]);
    }
}
