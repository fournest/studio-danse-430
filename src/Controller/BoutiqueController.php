<?php

namespace App\Controller;

use App\Entity\CommandeBoutique;
use App\Entity\CommandeBoutiqueLigne;
use App\Entity\Goodie;
use App\Entity\User;
use App\Enum\ModePaiementBoutique;
use App\Enum\ModeRetraitBoutique;
use App\Enum\StatutCommandeBoutique;
use App\Repository\CommandeBoutiqueRepository;
use App\Repository\GoodieRepository;
use App\Service\BoutiqueCartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BoutiqueController extends AbstractController
{
    /** Labels publics → valeurs stockées en base (admin). */
    private const CATEGORIES = [
        'Vêtements' => 'Vêtement',
        'Accessoires' => 'Accessoire',
        'Goodies' => 'Goodie',
    ];

    #[Route('/boutique', name: 'app_boutique_index', methods: ['GET'])]
    public function index(Request $request, GoodieRepository $goodieRepository, BoutiqueCartService $cart): Response
    {
        $categorieLabel = $request->query->getString('categorie') ?: null;
        $categorieValue = null;

        if ($categorieLabel && isset(self::CATEGORIES[$categorieLabel])) {
            $categorieValue = self::CATEGORIES[$categorieLabel];
        } elseif ($categorieLabel && \in_array($categorieLabel, self::CATEGORIES, true)) {
            $categorieValue = $categorieLabel;
            $categorieLabel = array_search($categorieValue, self::CATEGORIES, true) ?: $categorieLabel;
        } else {
            $categorieLabel = null;
        }

        return $this->render('boutique/index.html.twig', [
            'goodies' => $goodieRepository->findActifsByCategorie($categorieValue),
            'categories' => array_keys(self::CATEGORIES),
            'categorieActive' => $categorieLabel,
            'panierCount' => $cart->countItems(),
        ]);
    }

    #[Route('/boutique/{id}/ajouter', name: 'app_boutique_ajouter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ajouter(
        Goodie $goodie,
        Request $request,
        BoutiqueCartService $cart,
    ): Response {
        if (!$this->isCsrfTokenValid('boutique_ajouter' . $goodie->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez réessayer.');

            return $this->redirectToRoute('app_boutique_index');
        }

        if (!$goodie->isEstActif()) {
            $this->addFlash('danger', 'Cet article n’est plus disponible.');

            return $this->redirectToRoute('app_boutique_index');
        }

        $taille = trim($request->request->getString('taille')) ?: null;
        $quantite = max(1, $request->request->getInt('quantite', 1));
        $tailles = $goodie->getTaillesDisponibles();

        if ($tailles && (!$taille || !\in_array($taille, $tailles, true))) {
            $this->addFlash('danger', 'Veuillez sélectionner une taille valide.');

            return $this->redirectToRoute('app_boutique_index', [
                'categorie' => $request->request->getString('categorie') ?: null,
            ]);
        }

        if ($quantite > $goodie->getStock()) {
            $this->addFlash('danger', 'Stock insuffisant pour cet article.');

            return $this->redirectToRoute('app_boutique_index');
        }

        $cart->add($goodie, $taille, $quantite);
        $this->addFlash('success', sprintf('%s a été ajouté au panier.', $goodie->getNom()));

        return $this->redirectToRoute('app_boutique_panier');
    }

    #[Route('/boutique/panier', name: 'app_boutique_panier', methods: ['GET', 'POST'])]
    public function panier(Request $request, BoutiqueCartService $cart): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('boutique_panier', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $action = (string) $request->request->get('_action', 'update');
            $goodieId = $request->request->getInt('goodie_id');
            $taille = trim($request->request->getString('taille')) ?: null;

            if ($action === 'remove') {
                $cart->remove($goodieId, $taille);
                $this->addFlash('success', 'Article retiré du panier.');
            } elseif ($action === 'update') {
                $qty = $request->request->getInt('quantite', 1);
                $cart->updateQuantite($goodieId, $taille, $qty);
                $this->addFlash('success', 'Panier mis à jour.');
            }

            return $this->redirectToRoute('app_boutique_panier');
        }

        return $this->render('boutique/panier.html.twig', [
            'items' => $cart->getDetailedItems(),
            'total' => $cart->getTotal(),
        ]);
    }

    #[Route('/boutique/commande', name: 'app_boutique_commande', methods: ['GET', 'POST'])]
    public function commande(
        Request $request,
        BoutiqueCartService $cart,
        EntityManagerInterface $em,
    ): Response {
        if ($cart->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_boutique_index');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        $foyer = $user?->getFoyer();

        $defaults = [
            'nomComplet' => $foyer?->getNom() ?? '',
            'email' => $user?->getEmail() ?? '',
            'telephone' => $user?->getTelephone() ?? '',
            'adresse' => $foyer?->getAdresse() ?? '',
            'codePostal' => $foyer?->getCodePostal() ?? '',
            'ville' => $foyer?->getVille() ?? '',
            'modeRetrait' => ModeRetraitBoutique::RETRAIT_CLUB->value,
            'modePaiement' => ModePaiementBoutique::CHEQUE->value,
        ];

        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('boutique_commande', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $data = [
                'nomComplet' => trim($request->request->getString('nomComplet')),
                'email' => trim($request->request->getString('email')),
                'telephone' => trim($request->request->getString('telephone')),
                'adresse' => trim($request->request->getString('adresse')),
                'codePostal' => trim($request->request->getString('codePostal')),
                'ville' => trim($request->request->getString('ville')),
                'modeRetrait' => $request->request->getString('modeRetrait'),
                'modePaiement' => $request->request->getString('modePaiement'),
            ];
            $defaults = array_merge($defaults, $data);

            if ($data['nomComplet'] === '') {
                $errors[] = 'Indiquez votre nom.';
            }
            if ($data['email'] === '' || !filter_var($data['email'], \FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Indiquez un e-mail valide.';
            }

            $modeRetrait = ModeRetraitBoutique::tryFrom($data['modeRetrait']);
            $modePaiement = ModePaiementBoutique::tryFrom($data['modePaiement']);
            if (!$modeRetrait) {
                $errors[] = 'Choisissez un mode de retrait.';
            }
            if (!$modePaiement) {
                $errors[] = 'Choisissez un mode de paiement.';
            }
            if ($modeRetrait === ModeRetraitBoutique::LIVRAISON) {
                if ($data['adresse'] === '' || $data['codePostal'] === '' || $data['ville'] === '') {
                    $errors[] = 'Adresse complète requise pour la livraison.';
                }
            }

            $items = $cart->getDetailedItems();
            foreach ($items as $item) {
                if ($item['quantite'] > $item['goodie']->getStock()) {
                    $errors[] = sprintf('Stock insuffisant pour « %s ».', $item['goodie']->getNom());
                }
            }

            if ($errors === [] && $modeRetrait && $modePaiement) {
                $commande = new CommandeBoutique();
                $commande->setUser($user);
                $commande->setFoyer($foyer);
                $commande->setNomComplet($data['nomComplet']);
                $commande->setEmail($data['email']);
                $commande->setTelephone($data['telephone'] !== '' ? $data['telephone'] : null);
                $commande->setAdresse($data['adresse'] !== '' ? $data['adresse'] : null);
                $commande->setCodePostal($data['codePostal'] !== '' ? $data['codePostal'] : null);
                $commande->setVille($data['ville'] !== '' ? $data['ville'] : null);
                $commande->setModeRetrait($modeRetrait);
                $commande->setModePaiement($modePaiement);
                $commande->setStatut(
                    $modePaiement === ModePaiementBoutique::HELLOASSO
                        ? StatutCommandeBoutique::EN_ATTENTE
                        : StatutCommandeBoutique::CONFIRMEE
                );

                foreach ($items as $item) {
                    $ligne = new CommandeBoutiqueLigne();
                    $ligne->setGoodie($item['goodie']);
                    $ligne->setTaille($item['taille']);
                    $ligne->setQuantite($item['quantite']);
                    $ligne->setPrixUnitaire($item['prixUnitaire']);
                    $ligne->recalculerPrixTotal();
                    $commande->addLigne($ligne);

                    $item['goodie']->setStock($item['goodie']->getStock() - $item['quantite']);
                }

                $commande->recalculerTotal();
                $em->persist($commande);
                $em->flush();
                $cart->clear();

                $this->addFlash('success', 'Votre commande boutique a bien été enregistrée.');

                return $this->redirectToRoute('app_boutique_confirmation', ['id' => $commande->getId()]);
            }
        }

        return $this->render('boutique/commande.html.twig', [
            'items' => $cart->getDetailedItems(),
            'total' => $cart->getTotal(),
            'defaults' => $defaults,
            'errors' => $errors,
            'modesRetrait' => ModeRetraitBoutique::cases(),
            'modesPaiement' => ModePaiementBoutique::cases(),
            'isLoggedIn' => null !== $user,
        ]);
    }

    #[Route('/boutique/confirmation/{id}', name: 'app_boutique_confirmation', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirmation(CommandeBoutique $commande): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user && $commande->getUser() && $commande->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('boutique/confirmation.html.twig', [
            'commande' => $commande,
        ]);
    }
}
