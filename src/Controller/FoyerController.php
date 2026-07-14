<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Foyer;
use App\Entity\Danseur;
use App\Form\FoyerType;
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
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        // Si l'utilisateur n'a pas encore de foyer, on le redirige d'office vers la configuration
        if (!$foyer) {
            return $this->redirectToRoute('app_foyer_new');
        }

        return $this->render('foyer/index.html.twig', [
            'foyer' => $foyer,
            'danseurs' => $foyer->getDanseurs(), // Les danseurs appartiennent désormais au Foyer
        ]);
    }

    #[Route('/configuration', name: 'app_foyer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sécurité : Si l'utilisateur a déjà un foyer, on ne le laisse pas en créer un deuxième
        if ($user->getFoyer()) {
            return $this->redirectToRoute('app_foyer_index');
        }

        $foyer = new Foyer();
        $form = $this->createForm(FoyerType::class, $foyer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $foyer->setUser($user); // On lie le foyer à l'utilisateur connecté

            $em->persist($foyer);
            $em->flush();

            $this->addFlash('success', 'Votre dossier familial a bien été configuré ! Vous pouvez maintenant ajouter vos danseurs.');
            return $this->redirectToRoute('app_foyer_index');
        }

        return $this->render('foyer/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Configuration du dossier familial (Foyer)'
        ]);
    }

    #[Route('/ajouter-un-danseur', name: 'app_foyer_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        if (!$foyer) {
            $this->addFlash('error', 'Vous devez d’abord configurer votre foyer.');
            return $this->redirectToRoute('app_foyer_new');
        }

        $danseur = new Danseur();
        $form = $this->createForm(DanseurType::class, $danseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // SÉCURITÉ : Le danseur est directement et uniquement rattaché au FOYER
            $danseur->setFoyer($foyer);

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
        /** @var User $user */
        $user = $this->getUser();
        $foyer = $user->getFoyer();

        // 🔒 SÉCURITÉ CRITIQUE : On vérifie que le danseur appartient bien au foyer de l'utilisateur connecté
        if ($danseur->getFoyer() !== $foyer) {
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

    #[Route('/mon-foyer/desactiver', name: 'app_foyer_desactiver', methods: ['POST'])]
    public function desactiver(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            $user->setIsActif(false);
            $em->flush();
            // Déconnexion forcée
            return $this->redirectToRoute('app_logout');
        }
        return $this->redirectToRoute('app_foyer_index');
    }

    #[Route('/mon-foyer/supprimer', name: 'app_foyer_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            $em->remove($user); // Cascade delete supprimera le Foyer automatiquement
            $em->flush();
            return $this->redirectToRoute('app_home');
        }
        return $this->redirectToRoute('app_foyer_index');
    }
}
