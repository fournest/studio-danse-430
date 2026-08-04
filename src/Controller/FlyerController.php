<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use App\Repository\MediaRepository;
use App\Repository\SponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class FlyerController extends AbstractController
{
    private const FALLBACK_PHOTOS = [
        'images/fond-ecran-studio-danse-430.jpg',
        'images/logo.studio-danse-430.jpg',
        'images/fond-ecran-studio-danse-430.jpg',
    ];

    #[Route('/flyer', name: 'app_flyer', methods: ['GET'])]
    public function index(
        Request $request,
        CoursRepository $coursRepository,
        MediaRepository $mediaRepository,
        SponsorRepository $sponsorRepository,
    ): Response {
        $defaultTargetUrl = $this->generateUrl(
            'app_foyer_inscription_cours',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $titre = trim((string) $request->query->get('titre', 'Studio Danse 430'));
        $sousTitre = trim((string) $request->query->get('sous_titre', "L'excellence de la danse depuis 1976"));
        $badge = trim((string) $request->query->get('badge', 'INSCRIPTIONS SAISON 2026 - 2027'));
        $description = trim((string) $request->query->get(
            'description',
            'Rejoignez notre école de danse ! Des cours pour enfants, adolescents et adultes, toute la saison.'
        ));
        $tagsRaw = (string) $request->query->get(
            'tags',
            'Éveil & Initiation,Classique,Modern Jazz,Contemporain,Hip-Hop'
        );

        $mode = strtolower(trim((string) $request->query->get('mode', 'planning')));
        if (!\in_array($mode, ['simple', 'planning'], true)) {
            $mode = 'planning';
        }

        $targetUrl = $this->resolveTargetUrl($request, $defaultTargetUrl);

        $tags = array_values(array_filter(array_map(
            static fn (string $tag): string => trim($tag),
            explode(',', $tagsRaw)
        ), static fn (string $tag): bool => $tag !== ''));

        $displayUrl = preg_replace('#^https?://#i', '', $targetUrl) ?? $targetUrl;

        $coursParJour = [];
        $photos = [];
        if ($mode === 'planning') {
            $coursParJour = array_filter(
                $coursRepository->findGroupedByJour(),
                static fn (array $items): bool => $items !== []
            );
            $photos = $this->resolvePhotos($mediaRepository);
        }

        return $this->render('flyer/index.html.twig', [
            'titre' => $titre !== '' ? $titre : 'Studio Danse 430',
            'sous_titre' => $sousTitre,
            'badge' => $badge,
            'description' => $description,
            'tags' => $tags,
            'mode' => $mode,
            'target_url' => $targetUrl,
            'display_url' => $displayUrl,
            'cours_par_jour' => $coursParJour,
            'photos' => $photos,
            'background_image' => 'images/fond-ecran-studio-danse-430.jpg',
            'sponsors' => $sponsorRepository->findAll(),
        ]);
    }

    private function resolveTargetUrl(Request $request, string $defaultTargetUrl): string
    {
        $target = strtolower(trim((string) $request->query->get('target', '')));
        $targetUrl = trim((string) $request->query->get('target_url', ''));

        if ($target === 'home') {
            return $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if ($target === 'inscription') {
            return $defaultTargetUrl;
        }

        if ($targetUrl !== '' && filter_var($targetUrl, \FILTER_VALIDATE_URL) !== false) {
            return $targetUrl;
        }

        return $defaultTargetUrl;
    }

    /**
     * @return list<string> Chemins asset relatifs (3 emplacements)
     */
    private function resolvePhotos(MediaRepository $mediaRepository): array
    {
        $photos = [];
        foreach ($mediaRepository->findLatestLocalImages(3) as $media) {
            if ($media->getImageName()) {
                $photos[] = 'uploads/galerie/'.$media->getImageName();
            }
        }

        $i = 0;
        while (\count($photos) < 3) {
            $photos[] = self::FALLBACK_PHOTOS[$i % \count(self::FALLBACK_PHOTOS)];
            ++$i;
        }

        return \array_slice($photos, 0, 3);
    }
}
