<?php

namespace App\Controller\Admin;

use App\Entity\Actualite;
use App\Form\FlyerConfigType;
use App\Repository\ActualiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_TRESORIER')]
#[AdminRoute('/flyer-creator', name: 'flyer_creator')]
final class FlyerAdminController extends AbstractController
{
    #[AdminRoute('/', name: 'index', options: ['methods' => ['GET']])]
    public function index(): Response
    {
        $form = $this->createFlyerForm($this->getDefaultFlyerData());

        return $this->renderFlyerPage($form);
    }

    #[AdminRoute('/generate', name: 'generate', options: ['methods' => ['POST']])]
    public function generate(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ActualiteRepository $actualiteRepository,
    ): Response {
        $form = $this->createFlyerForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->redirectToRoute('admin_flyer_creator_index');
        }

        if (!$form->isValid()) {
            $this->addFlyerValidationFlash($form);

            return $this->renderFlyerPage($form);
        }

        /** @var array<string, mixed> $data */
        $data = $form->getData();

        $titreFlyer = trim((string) ($data['titre'] ?? 'Studio Danse 430'));
        $badge = trim((string) ($data['badge'] ?? ''));
        $sousTitre = trim((string) ($data['sous_titre'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $tagsRaw = trim((string) ($data['tags'] ?? ''));
        $mode = strtolower(trim((string) ($data['mode'] ?? 'planning')));
        if (!\in_array($mode, ['simple', 'planning'], true)) {
            $mode = 'planning';
        }

        $targetUrl = $this->resolveTargetUrl(
            (string) ($data['target'] ?? 'inscription'),
            trim((string) ($data['target_url'] ?? '')),
        );

        $publierDansFil = (bool) ($data['publier_dans_fil'] ?? true);

        $titreActualite = $badge !== '' ? $badge : $titreFlyer;
        $slug = $this->generateUniqueSlug($titreActualite, $slugger, $actualiteRepository);

        $contenu = $this->buildActualiteContenu($description, $tagsRaw, $titreFlyer, $sousTitre, $targetUrl);

        $actualite = new Actualite();
        $actualite->setTitre($titreActualite);
        $actualite->setSlug($slug);
        $actualite->setChapeau($sousTitre !== '' ? $sousTitre : null);
        $actualite->setContenu($contenu);
        $actualite->setIsPublished(true);
        $actualite->setPublierDansFil($publierDansFil);

        $em->persist($actualite);
        $em->flush();

        $this->addFlash(
            'success',
            $publierDansFil
                ? 'Flyer généré et événement publié dans le fil d’actualités du site.'
                : 'Flyer généré. L’événement n’apparaîtra pas dans le fil d’actualités (lien direct uniquement).'
        );

        return $this->redirectToRoute('app_flyer', [
            'titre' => $titreFlyer,
            'sous_titre' => $sousTitre,
            'badge' => $badge,
            'description' => $description,
            'tags' => $tagsRaw,
            'mode' => $mode,
            'target' => $data['target'] ?? 'inscription',
            'target_url' => $targetUrl,
        ]);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function createFlyerForm(?array $data = null): FormInterface
    {
        return $this->createForm(FlyerConfigType::class, $data, [
            'action' => $this->generateUrl('admin_flyer_creator_generate'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultFlyerData(): array
    {
        return [
            'titre' => 'Studio Danse 430',
            'sous_titre' => "L'excellence de la danse depuis 1976",
            'badge' => 'INSCRIPTIONS SAISON 2026 - 2027',
            'description' => 'Rejoignez notre école de danse ! Des cours pour enfants, adolescents et adultes, toute la saison.',
            'tags' => 'Éveil & Initiation,Classique,Modern Jazz,Contemporain,Hip-Hop',
            'mode' => 'planning',
            'target' => 'inscription',
            'target_url' => '',
            'publier_dans_fil' => true,
        ];
    }

    private function renderFlyerPage(FormInterface $form): Response
    {
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

    private function addFlyerValidationFlash(FormInterface $form): void
    {
        $messages = [];
        foreach ($form->getErrors(true) as $error) {
            $origin = $error->getOrigin();
            $fieldName = $origin?->getName() ?? 'formulaire';
            $messages[] = sprintf('%s : %s', $fieldName, $error->getMessage());
        }

        $summary = $messages !== []
            ? implode(' · ', array_unique($messages))
            : 'Vérifiez les champs marqués d\'un astérisque (*).';

        $this->addFlash('danger', 'Le formulaire flyer est invalide. '.$summary);
    }

    private function resolveTargetUrl(string $target, string $targetUrl): string
    {
        $target = strtolower(trim($target));

        if ($target === 'home') {
            return $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if ($target === 'inscription') {
            return $this->generateUrl('app_foyer_inscription_cours', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if ($targetUrl !== '' && filter_var($targetUrl, \FILTER_VALIDATE_URL) !== false) {
            return $targetUrl;
        }

        return $this->generateUrl('app_foyer_inscription_cours', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function buildActualiteContenu(
        string $description,
        string $tagsRaw,
        string $titreFlyer,
        string $sousTitre,
        string $targetUrl,
    ): string {
        $parts = [];

        if ($description !== '') {
            $parts[] = '<p>'.nl2br(htmlspecialchars($description, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')).'</p>';
        }

        $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw))));
        if ($tags !== []) {
            $items = implode('', array_map(
                static fn (string $tag): string => '<li>'.htmlspecialchars($tag, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').'</li>',
                $tags
            ));
            $parts[] = '<ul>'.$items.'</ul>';
        }

        $meta = array_filter([
            $titreFlyer !== '' ? 'Flyer : '.htmlspecialchars($titreFlyer, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') : null,
            $sousTitre !== '' ? htmlspecialchars($sousTitre, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') : null,
        ]);
        if ($meta !== []) {
            $parts[] = '<p class="text-muted"><em>'.implode(' — ', $meta).'</em></p>';
        }

        $parts[] = '<p><a href="'.htmlspecialchars($targetUrl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').'" target="_blank" rel="noopener">En savoir plus / S’inscrire</a></p>';

        return implode("\n", $parts);
    }

    private function generateUniqueSlug(string $titre, SluggerInterface $slugger, ActualiteRepository $repository): string
    {
        $base = strtolower((string) $slugger->slug($titre));
        $base = $base !== '' ? $base : 'evenement';
        $slug = $base;
        $i = 1;

        while (null !== $repository->findOneBy(['slug' => $slug])) {
            $slug = $base.'-'.$i;
            ++$i;
        }

        return $slug;
    }
}
