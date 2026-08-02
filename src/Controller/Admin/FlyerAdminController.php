<?php

namespace App\Controller\Admin;

use App\Form\FlyerConfigType;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TRESORIER')]
#[AdminRoute('/flyer-creator', name: 'flyer_creator')]
final class FlyerAdminController extends AbstractController
{
    #[AdminRoute('/', name: 'index')]
    public function index(): Response
    {
        $form = $this->createForm(FlyerConfigType::class, [
            'titre' => 'Studio Danse 430',
            'sous_titre' => "L'excellence de la danse depuis 1976",
            'badge' => 'INSCRIPTIONS SAISON 2026 - 2027',
            'description' => 'Rejoignez notre école de danse ! Des cours pour enfants, adolescents et adultes, toute la saison.',
            'tags' => 'Éveil & Initiation,Classique,Modern Jazz,Contemporain,Hip-Hop',
            'mode' => 'planning',
            'target' => 'inscription',
            'target_url' => '',
        ], [
            'method' => 'GET',
            'action' => $this->generateUrl('app_flyer'),
        ]);

        return $this->render('admin/flyer/create.html.twig', [
            'form' => $form->createView(),
            'url_inscription' => $this->generateUrl(
                'app_foyer_inscription_cours',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'url_home' => $this->generateUrl(
                'app_home',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
    }
}
