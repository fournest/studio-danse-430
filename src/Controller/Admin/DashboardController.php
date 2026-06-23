<?php

namespace App\Controller\Admin;

use App\Entity\StatutDossier;
use App\Repository\CoursRepository;
use App\Repository\DanseurRepository;
use App\Repository\InscriptionRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly DanseurRepository $danseurRepository,
        private readonly CoursRepository $coursRepository,
        private readonly InscriptionRepository $inscriptionRepository,
    ) {
    }

    public function index(): Response
    {
        // Calcul des statistiques
        $totalDanseurs = $this->danseurRepository->count([]);
        $totalCours = $this->coursRepository->count([]);
        $totalInscriptions = $this->inscriptionRepository->count([]);
        
        $dossiersEnAttente = $this->inscriptionRepository->count([
            'statutDossier' => StatutDossier::EN_ATTENTE
        ]);

        // Récupération des 5 dernières inscriptions (triées par ID décroissant)
        $dernieresInscriptions = $this->inscriptionRepository->findBy(
            [], 
            ['id' => 'DESC'], 
            5
        );

        return $this->render('admin/dashboard.html.twig', [
            'total_danseurs' => $totalDanseurs,
            'total_cours' => $totalCours,
            'total_inscriptions' => $totalInscriptions,
            'dossiers_en_attente' => $dossiersEnAttente,
            'dernieres_inscriptions' => $dernieresInscriptions,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Studio Danse 430 — Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Retour au site', 'fa fa-arrow-left-long', $this->generateUrl('app_home'));
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Adhérents');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::linkTo(DanseurCrudController::class, 'Danseurs', 'fa fa-person-walking');

        yield MenuItem::section('Activités');
        yield MenuItem::linkTo(CoursCrudController::class, 'Cours', 'fa fa-music');
        yield MenuItem::linkTo(InscriptionCrudController::class, 'Inscriptions', 'fa fa-file-signature');

        yield MenuItem::section('Événements');
        yield MenuItem::linkTo(GalaCrudController::class, 'Galas', 'fa fa-star');
        yield MenuItem::linkTo(SalleCrudController::class, 'Salles', 'fa fa-location-dot');
    }
}