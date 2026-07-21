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
use App\Controller\Admin\FoyerCrudController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    
    public function __construct(
        private readonly DanseurRepository $danseurRepository,
        private readonly CoursRepository $coursRepository,
        private readonly InscriptionRepository $inscriptionRepository,
    ) {}

    public function index(): Response
    {
        // 1. Récupération des données en Base
        $totalDanseurs = $this->danseurRepository->count([]);
        $totalCours = $this->coursRepository->count([]);
        $totalInscriptions = $this->inscriptionRepository->count([]);
        
        $dossiersEnAttente = $this->inscriptionRepository->count([
            'statutDossier' => StatutDossier::EN_ATTENTE
        ]);
        
        $dernieresInscriptions = $this->inscriptionRepository->findBy(
            [],
            ['id' => 'DESC'],
            5
        );

        // 2. Envoi au template avec les clés EXACTES attendues par le fichier Twig
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
            ->setTitle('Studio Danse 430 — Administration')
            ->setFaviconPath('favicon.ico')
            // On utilise le domaine de traduction natif d'EasyAdmin pour avoir les boutons de base en français
            ->setTranslationDomain('EasyAdminBundle');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Retour au site', 'fa fa-arrow-left-long', $this->generateUrl('app_home'));
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Adhérents');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::linkTo(FoyerCrudController::class, 'Foyers / Familles', 'fa fa-house-user'); 
        yield MenuItem::linkTo(DanseurCrudController::class, 'Danseurs', 'fa fa-person-walking');

        yield MenuItem::section('Activités');
        yield MenuItem::linkTo(CoursCrudController::class, 'Cours', 'fa fa-music');
        yield MenuItem::linkTo(InscriptionCrudController::class, 'Inscriptions', 'fa fa-file-signature');
        yield MenuItem::linkTo(GalaCrudController::class, 'Galas', 'fa fa-star');
        yield MenuItem::linkTo(SalleCrudController::class, 'Salles', 'fa fa-location-dot');
        yield MenuItem::linkTo(CostumeCrudController::class, 'Costumes', 'fa fa-shirt');
        yield MenuItem::linkTo(ReservationCostumeCrudController::class, 'Réservations de costumes', 'fa fa-shopping-cart');

        yield MenuItem::section('Sponsors');
        yield MenuItem::linkTo(SponsorCrudController::class, 'Sponsors', 'fa fa-handshake');
    }
}