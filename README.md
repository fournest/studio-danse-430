<div align="center">

# 🩰 Studio Danse 430

### _L'excellence de la danse depuis 1976_

Site vitrine & back-office de gestion pour une association de danse — identité **Noir & Or** ⚫🟡

<br>

![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=for-the-badge&logo=symfony&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Twig](https://img.shields.io/badge/Twig-3-1E1E1E?style=for-the-badge&logo=twig&logoColor=white)
![EasyAdmin](https://img.shields.io/badge/EasyAdmin-5-2D9CDB?style=for-the-badge)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-38BDF8?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Doctrine](https://img.shields.io/badge/Doctrine_ORM-3-FC6A31?style=for-the-badge)
![Licence](https://img.shields.io/badge/Licence-Propriétaire-red?style=for-the-badge)

</div>

---

## 📖 Présentation

**Studio Danse 430** est l'application web d'une **association de danse fondée en 1976**. Elle couvre :

- 🎭 **Site vitrine** — Accueil, planning des cours (avec tarifs), galerie, Gala, costumes, sponsors
- 👨‍👩‍👧 **Espace familles** — Foyer, danseurs, co-parent (invitation), stepper d’inscription (cours → santé → règlement → attestation)
- 💃 **Espace professeur** — Consultation des cours et élèves (`ROLE_PROF`)
- 🖨️ **Flyers** — Génération / impression A5 (recto planning + verso sponsors)
- 🛠️ **Back-office EasyAdmin** — Adhérents, cours/tarifs/capacité, inscriptions, paiements, liste d’attente, remises, galas, costumes, galerie, créateur de flyer

Identité visuelle : fond **noir**, accents **or (`#FFD700`)**, typographie marquée.

---

## 🧱 Stack technique

| Domaine | Technologie | Détails |
| :--- | :--- | :--- |
| 🐘 **Langage** | PHP `8.2+` | Typage strict, enums, attributs |
| 🎼 **Framework** | Symfony `7.4` | MVC, AssetMapper, Security, Mailer, RateLimiter |
| 🖋️ **Templating** | Twig `3` | Globals RIB + réseaux sociaux |
| 🗄️ **ORM** | Doctrine ORM `3` + Migrations | Entités, relations, migrations |
| 🔐 **Back-office** | EasyAdmin `5` | CRUD, dashboard, menu FR |
| 🛡️ **Sécurité** | Symfony Security | Login throttling, verify-email, reset-password, rôles métier |
| 🎨 **CSS public** | Tailwind CSS `v4` | `symfonycasts/tailwind-bundle` |
| 🎨 **CSS admin** | Tailwind CSS `v3` CDN | `preflight: false` (ne casse pas EasyAdmin) |
| 📤 **Uploads** | EasyAdmin + VichUploader | Costumes · Galerie · certificats médicaux |
| ⚡ **Front** | Symfony UX (Turbo + Stimulus) | dont contrôleur `clipboard` (copie IBAN) |
| 🗃️ **BDD** | MySQL / MariaDB / PostgreSQL | Via `DATABASE_URL` ; Postgres optionnel (Docker) |
| 🎟️ **Billetterie** | Billetweb | Gala via `billetwebEventId` (lien externe) |
| 💳 **Paiement** | Chèque · Virement · ANCV · Pass’Sport · Espèces · HelloAsso | Échelonnement + aides soustraites du solde |

---

## ✨ Fonctionnalités clés

### 🌐 Site public

- 🏠 **Accueil** — Hero, aperçu des cours **avec tarifs**, actualités, sponsors
- 📅 **Cours** — `/cours`, `/cours/{id}` : jour, horaire, durée, professeurs, capacité, **tarif saison**
- 🖼️ **Galerie** — Albums & médias (local / Instagram / Facebook / YouTube)
- 👗 **Costumes** — Catalogue & réservation (membres connectés)
- 🎟️ **Gala** — Réservation via Billetweb
- 👤 **Compte** — Inscription + vérification e-mail, login, mot de passe oublié
- 🎫 **Support** — Ticket interne (`ROLE_BUREAU` / `TRESORIER` / `PROF`)
- 🔗 **Footer** — Liens Facebook / Instagram configurables (`SOCIAL_*`)

### 👨‍👩‍👧 Espace familial (`/mon-foyer`)

Stepper guidé côté foyer :

1. **Famille** — Configurer le dossier (`/mon-foyer/configuration`) + ajouter des danseurs
2. **Choix des cours** — `/mon-foyer/inscription-cours`
   - Cases par danseur, filtrage par **année de naissance**
   - Places restantes / **liste d’attente**
   - Récap cotisation (gratuité 2020, dégressivité foyer, remises bureau)
3. **Santé** — QS Sport / certificat médical par danseur
4. **Règlement & Facture** — paiement foyer unique puis attestation

**Après enregistrement du règlement :**
- Badge **⏳ En attente d'encaissement** (tant que le trésorier n’a pas encaissé)
- Étape 4 cochée · CTA paiement masqués
- Accès **Attestation / Facture CE** + détail des règlements

**Familles recomposées / co-parent :**
- Parent 2 au niveau **Danseur** (email, invitation e-mail signée)
- Le 2ᵉ parent connecté voit les fiches en **lecture seule**
- Titulaire seul : inscriptions & modifications

> L’ancienne URL `/inscription` redirige vers `/mon-foyer/inscription-cours`.

### 💶 Paiement foyer

Sur `/mon-foyer/inscription/{id}/paiement` :

1. **Aides optionnelles** — Pass’Sport, chèques vacances ANCV, espèces (soustraites du total)
2. **Solde à échelonner** — Chèques **ou** virements programmés en **1× / 3× / 10×**
   - Chèque : émetteur + consignes de dépôt
   - Virement : IBAN / BIC / titulaire + libellé unique (`COTIS-2026-NOMFOYER`)

Chaque échéance est une ligne `Paiement` (mode, montant, date prévue, référence).

### 🎫 Capacité des cours & liste d’attente

| Élément | Détail |
| :--- | :--- |
| **Capacité** | `Cours.capaciteMax` (défaut 25) |
| **Places occupées** | Inscriptions hors liste d’attente (brouillon → validée) |
| **Complet** | Proposition **liste d’attente** |
| **Facturation** | `estEnListeDAttente = true` → exclu de la cotisation |
| **Admin** | Jauge d’effectif, confirmation d’une place (recalcule la cotisation) |

### 💶 Cotisations — saison 2026/2027

Service : `CotisationCalculatorService` (`calculerTotalFoyer`).

| Règle | Détail |
| :--- | :--- |
| **Tarif de base** | `Cours.tarif` (EasyAdmin) |
| **Gratuité 2020** | Enfant né en **2020** avec ≥ 2 cours → le **moins cher** est gratuit |
| **Remise foyer** | 1 cours : 0 % · 2 cours : **−20 %** · 3+ : **−30 %** |
| **Remise bureau** | `Foyer.remiseManuelle` + `Inscription.remiseManuelle` |
| **Liste d’attente** | Non facturée tant qu’une place n’est pas confirmée |

```bash
php bin/console app:test-cotisation-calculator
php bin/console app:test-echelonnement
```

### 🖨️ Flyer

- **Impression** — `/flyer` (paramètres query, QR code, modes simple / planning)
- **Admin** — menu **Créer un Flyer** (`/admin/flyer-creator`)

### 💃 Espace Professeur (`ROLE_PROF`)

`/espace-prof` — consultation des cours et élèves (pas de saisie de présence).

### 🛠️ Administration (EasyAdmin)

Accès : **`ROLE_TRESORIER`** ou **`ROLE_BUREAU`**.

- Dashboard KPI
- Users, Foyers (remise manuelle), Danseurs (parent 2, santé)
- **Cours** — tarif, capacité, bornes d’âge, jauge, liste d’attente
- **Inscriptions** — tunnel, liste d’attente, remises, HelloAsso
- **Paiements** — modes, encaissement, validation
- Galas, Salles, Costumes, Réservations, Sponsors, Albums / Médias
- **Créer un Flyer**

---

## 🚀 Installation locale

### Prérequis

- PHP `>= 8.2` · Composer `2.x` · Git
- MySQL / MariaDB (WAMP) ou PostgreSQL
- Docker *(optionnel)* · Symfony CLI *(recommandé)*

### 1. Cloner & dépendances

```bash
git clone <url-du-depot> studio-danse-430
cd studio-danse-430
composer install
```

### 2. Environnement (`.env.local`)

```dotenv
APP_ENV=dev
APP_SECRET=changez_moi_par_une_chaine_aleatoire

# MySQL / MariaDB (ex. WAMP)
DATABASE_URL="mysql://root:@127.0.0.1:3306/studio_danse_430?serverVersion=8.0.32&charset=utf8mb4"

# PostgreSQL (Docker Compose)
# DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

MAILER_DSN=null://null

BILLETWEB_API_KEY=
BILLETWEB_API_URL=https://api.billetweb.fr/v1

HELLOASSO_CLIENT_ID=
HELLOASSO_CLIENT_SECRET=
HELLOASSO_API_URL=https://api.helloasso.com/v5
HELLOASSO_CAMPAIGN_URL=

# Coordonnées bancaires (virement)
CLUB_IBAN="FR76 1234 5678 9012 3456 7890 123"
CLUB_BIC="ABCDFR21XXX"
CLUB_TITULAIRE="Studio Danse 430"

# Réseaux sociaux (footer)
SOCIAL_FACEBOOK_URL="https://www.facebook.com/studiodanse430"
SOCIAL_INSTAGRAM_URL="#"

N8N_WEBHOOK_URL=
N8N_WEBHOOK_TOKEN=
```

> Secrets de prod uniquement dans `.env.local` (non versionné). Remplacer IBAN / BIC / URLs sociales par les valeurs réelles du club.

### 3. Base de données

```bash
# Optionnel Postgres
docker compose up -d

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Comptes équipe

```bash
php bin/console app:create-staff tresorier@studio430.fr "MotDePasseSolide!" tresorier 0612345678
php bin/console app:create-staff bureau@studio430.fr "MotDePasseSolide!" bureau 0612345678
php bin/console app:create-staff prof@studio430.fr "MotDePasseSolide!" prof 0612345678
```

> Pour l’Espace Prof, l’e-mail du compte doit correspondre au champ **professeur** des cours.

### 5. Données de test *(optionnel)*

```bash
php bin/console app:seed-test-family
# Parent : parent.test@studio430.fr / Password123!

php bin/console app:test-blended-family
# Titulaire : chloe.parent@studio430.fr
# 2ᵉ parent : mathieu.parent@studio430.fr

php bin/console app:seed-sponsors
```

### 6. CSS & serveur

```bash
php bin/console tailwind:build
# ou : php bin/console tailwind:build --watch

symfony server:start
# ou : php -S localhost:8000 -t public/
```

| URL | Rôle |
| :--- | :--- |
| [http://localhost:8000](http://localhost:8000) | Site public |
| [http://localhost:8000/mon-foyer](http://localhost:8000/mon-foyer) | Espace familial |
| [http://localhost:8000/mon-foyer/inscription-cours](http://localhost:8000/mon-foyer/inscription-cours) | Choix des cours |
| [http://localhost:8000/flyer](http://localhost:8000/flyer) | Flyer imprimable |
| [http://localhost:8000/espace-prof](http://localhost:8000/espace-prof) | Espace Prof |
| [http://localhost:8000/admin](http://localhost:8000/admin) | Back-office |

---

## 🧰 Commandes utiles

```bash
# Métier
php bin/console app:create-staff <email> <password> <bureau|tresorier|prof> [tel]
php bin/console app:seed-test-family
php bin/console app:test-blended-family
php bin/console app:test-cotisation-calculator
php bin/console app:test-echelonnement
php bin/console app:seed-sponsors

# Doctrine
php bin/console make:migration
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate

# Cache / debug
php bin/console cache:clear
php bin/console debug:router
php bin/console about

# Front
php bin/console tailwind:build --watch
php bin/console importmap:install
php bin/console asset-map:compile
```

---

## 📂 Structure du projet

```text
studio-danse-430/
├── compose.yaml
├── config/
├── migrations/
├── public/
│   ├── images/
│   └── uploads/          # costumes/ · galerie/
├── src/
│   ├── Command/          # staff, seeds, tests cotisation / échelonnement
│   ├── Controller/       # Public + Foyer
│   │   └── Admin/        # EasyAdmin + FlyerAdmin
│   ├── Dto/              # CotisationDetail, breakdowns
│   ├── Entity/           # User, Foyer, Danseur, Cours, Inscription, Paiement…
│   ├── Enum/             # ModePaiement, StatutInscription, StatutPaiement…
│   ├── Form/
│   ├── Repository/
│   ├── Security/         # InscriptionTunnelVoter
│   └── Service/          # Cotisation, Echelonnement, VirementLibelle, Mailers…
├── templates/
│   ├── admin/ · home/ · cours/ · foyer/ · flyer/
│   ├── emails/ · galerie/ · costume/ · gala/ · security/ …
│   └── base.html.twig
└── assets/
    └── controllers/      # Stimulus (clipboard, csrf…)
```

---

## 🗃️ Modèle de données (aperçu)

```text
User ── email · telephone · isVerified · isActif · roles
 └── 1,1 ── Foyer
              · coordonnées · remiseManuelle · referenceVirement
              └── 1,N ── Danseur
                           · identité · dateNaissance · parent2* · parent2InvitedAt
                           · statutSante
                           └── 1,N ── Inscription ── Cours
                                        · saison · statut · estEnListeDAttente
                                        · montantTotal · remiseManuelle
                                        └── 1,N ── Paiement
                                                     · mode · montant · statut
                                                     · reference · dateEncaissementPrevue

Cours ── nom · jour · heure · professeurs · capaciteMax
         · dureeMinutes · tarif · anneeNaissanceMin/Max

Album ── 1,N Media     Costume ── ReservationCostume     Gala ── Salle
Sponsor · Actualite · ResetPasswordRequest
```

**Enums :** `StatutInscription` · `StatutDossier` · `StatutPaiement` · `ModePaiement` · `StatutSante` · `StatutReservation` · `ModeLivraison` · `TypeMedia`

**Rôles :** `ROLE_USER` · `ROLE_PROF` · `ROLE_TRESORIER` · `ROLE_BUREAU` (hérite trésorier + prof)

---

## 📌 Notes métier

- Pas d’entité `Tarif` séparée : le prix vit sur **`Cours.tarif`**.
- Les tarifs publics ne s’affichent que si `tarif > 0`.
- Le règlement est **centralisé au foyer** (aides + échéancier), pas cours par cours.
- Libellé virement unique mémorisé sur `Foyer.referenceVirement` (ex. `COTIS-2026-DUPONT`).
- Les RIB (`CLUB_*`) et les réseaux (`SOCIAL_*`) sont exposés en globals Twig.
- Un cours complet n’empêche pas l’inscription : **liste d’attente** non facturée jusqu’à confirmation bureau.

---

<div align="center">

⚫🟡 **Studio Danse 430** — _École de danse depuis 1976_

Fait avec ❤️ et Symfony 7.4

</div>
