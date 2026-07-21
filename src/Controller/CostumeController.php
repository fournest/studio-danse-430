<?php

namespace App\Controller;

use App\Repository\CostumeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ReservationCostume;
use App\Form\CostumeReservationType;
use App\Entity\Costume;

final class CostumeController extends AbstractController
{
    #[Route('/location-costumes', name: 'app_costume_index', methods: ['GET'])]
    public function index(CostumeRepository $costumeRepository): Response
    {
        return $this->render('costume/index.html.twig', [
            'costumes' => $costumeRepository->findAll(),
        ]);
    }

    #[Route('/location-costumes/{id}/reserver', name: 'app_costume_reserver', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function reserver(
        Costume $costume,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = new ReservationCostume();
        $reservation->setCostume($costume);
        $reservation->setUser($this->getUser());

        // Pré-remplissage optionnel de la taille si renseignée sur la fiche costume
        if ($costume->getTaille()) {
            $reservation->setTaille($costume->getTaille());
        }

        $form = $this->createForm(CostumeReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Vérification du stock disponible
            if ($costume->getQuantite() < $reservation->getQuantite()) {
                $this->addFlash('danger', 'Désolé, la quantité demandée dépasse le stock disponible (' . $costume->getQuantite() . ' restant(s)).');
                return $this->redirectToRoute('app_costume_reserver', ['id' => $costume->getId()]);
            }

            // 2. Calcul du prix total
            $prixUnitaire = (float) $costume->getPrix();
            $prixTotal = $prixUnitaire * $reservation->getQuantite();
            $reservation->setPrixTotal((string) $prixTotal);

            // 3. Décrémentation du stock du costume
            $costume->setQuantite($costume->getQuantite() - $reservation->getQuantite());

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de réservation a bien été enregistrée ! L\'équipe vous recontactera sous peu.');

            return $this->redirectToRoute('app_costume_index');
        }

        return $this->render('costume/reserver.html.twig', [
            'costume' => $costume,
            'form' => $form->createView(),
        ]);
    }
}
