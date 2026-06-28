<?php

namespace App\Controller;

use App\Entity\Danseur;
use App\Form\DanseurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-foyer')]
#[IsGranted('ROLE_USER')]
class FoyerController extends AbstractController
{
    #[Route('', name: 'app_foyer_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        return $this->render('foyer/index.html.twig', [
            'danseurs' => $user->getDanseurs(), // ✨ Plus de rouge ici
        ]);
    }

    #[Route('/ajouter-un-danseur', name: 'app_foyer_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $danseur = new Danseur();
        $form = $this->createForm(DanseurType::class, $danseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // Sécurité : On force le parent à être l'utilisateur actuellement connecté
            $danseur->setParent($user);

            $em->persist($danseur);
            $em->flush();

            $this->addFlash('success', $danseur->getPrenom() . ' a bien été ajouté(e) au foyer !');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter un membre au foyer'
        ]);
    }

    #[Route('/modifier-un-danseur/{id}', name: 'app_foyer_edit', methods: ['GET', 'POST'])]
    public function edit(Danseur $danseur, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 🔒 SÉCURITÉ CRITIQUE : On vérifie que le danseur appartient bien à l'utilisateur connecté
        if ($danseur->getParent() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas l'autorisation de modifier ce profil.");
        }

        $form = $this->createForm(DanseurType::class, $danseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Le profil de ' . $danseur->getPrenom() . ' a été mis à jour.');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le profil de ' . $danseur->getPrenom()
        ]);
    }
}