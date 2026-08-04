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

- 🎭 **Site vitrine** — Accueil (disciplines regroupées), planning hebdomadaire, galerie, Gala, sponsors
- 👨‍👩‍👧 **Inscriptions familles** — Tunnel foyer dédié aux cours (santé, cotisation, échéancier)
- 👕 **Boutique** — Tunnel e-commerce séparé (goodies & vêtements)
- 👗 **Location costumes** — Tunnel hors-gala dédié (soirées / événements)
- 💃 **Espace professeur** — Consultation des cours et élèves (`ROLE_PROF`)
- 🖨️ **Flyers** — Génération / impression A5
- 🛠️ **Back-office EasyAdmin** — Adhérents, cours, inscriptions, paiements, boutique, costumes, galerie

Identité visuelle : fond **noir**, accents **or (`#FFD700`)**.

Les **3 tunnels de paiement** sont indépendants :

| Tunnel | Portée | Paiement |
| :--- | :--- | :--- |
| **1 · Foyer** `/mon-foyer` | Cours, cotisation, dégressivité, santé, échéancier | Chèque / virement / Pass’Sport / ANCV / HelloAsso |
| **2 · Boutique** `/boutique` | Goodies & vêtements | 1× (HelloAsso / CB, chèque ou espèces au club) |
| **3 · Costumes** `/costumes` | Location hors-gala | 1× + caution au retrait |

---

## 🧱 Stack technique

| Domaine | Technologie | Détails |
| :--- | :--- | :--- |
| 🐘 **Langage** | PHP `8.2+` | Typage strict, enums, attributs |
| 🎼 **Framework** | Symfony `7.4` | MVC, AssetMapper, Security, Mailer, RateLimiter |
| 🖋️ **Templating** | Twig `3` | Globals RIB + réseaux sociaux |
| 🗄️ **ORM** | Doctrine ORM `3` + Migrations | Entités, relations, migrations |
| 🔐 **Back-office** | EasyAdmin `5` | CRUD, dashboard |
| 🛡️ **Sécurité** | Symfony Security | Login throttling, verify-email, reset-password |
| 🎨 **CSS public** | Tailwind CSS `v4` | `symfonycasts/tailwind-bundle` (pas de npm) |
| 📤 **Uploads** | VichUploader | Costumes · Galerie · Goodies · certificats |
| ⚡ **Front** | Symfony UX (Turbo + Stimulus) | |
| 🗃️ **BDD** | MySQL / MariaDB / PostgreSQL | Via `DATABASE_URL` |
| 🎟️ **Billetterie** | Billetweb | Gala (lien externe) |
| 💳 **Paiement** | Chèque · Virement · ANCV · Pass’Sport · Espèces · HelloAsso | Pas de Stripe |

---

## ✨ Fonctionnalités clés

### 🌐 Site public

- 🏠 **Accueil** — Disciplines regroupées (plusieurs créneaux sur une seule fiche), actualités, sponsors
- 📅 **Planning** — `/cours` : agenda semaine (Lun → Dim), grille desktop + accordéon mobile
- 🖼️ **Galerie** — Albums & médias
- 👕 **Boutique** — Catalogue, panier session, commande, confirmation
- 👗 **Costumes** — Catalogue hors-gala, réservation (taille en liste déroulante), validation, confirmation
- 🎟️ **Gala** — Réservation via Billetweb
- 👤 **Compte** — Inscription + vérification e-mail, login, mot de passe oublié

### 👨‍👩‍👧 Tunnel 1 — Inscriptions cours (`/mon-foyer`)

Stepper guidé :

1. **Famille** — Configuration foyer + danseurs
2. **Choix des cours** — Filtrage par **âge** (`ageMin` / `ageMax`), places / liste d’attente, cotisation live
3. **Santé** — QS Sport / certificat médical
4. **Règlement & Facture** — aides + échéancier 1× / 3× / 10×, attestation

> Boutique et costumes ne font **plus** partie de ce tunnel.

**Co-parent :** invitation e-mail ; le 2ᵉ parent voit le foyer en lecture seule.

### 👕 Tunnel 2 — Boutique

```text
/boutique → /boutique/panier → /boutique/commande → /boutique/confirmation/{id}
```

- Panier en **session**
- Entité `CommandeBoutique` (+ lignes)
- Coordonnées préremplies si connecté
- Retrait club ou livraison · règlement 1×

### 👗 Tunnel 3 — Location costumes

```text
/costumes/{id}/reserver → /costumes/{id}/validation → /costumes/confirmation/{id}
```

- Taille via `<select>` (`Costume::getTaillesAsArray()` — ex. `S, M, L` ou `S à L`)
- Confirmation montant + caution au retrait
- Cycle : Demandée → Validée → En cours → Restituée

### 💶 Cotisations cours — saison 2026/2027

Service : `CotisationCalculatorService`.

| Règle | Détail |
| :--- | :--- |
| **Tarif de base** | `Cours.tarif` (EasyAdmin) |
| **Gratuité 2020** | ≥ 2 cours → le moins cher est gratuit |
| **Remise foyer** | 1 cours : 0 % · 2 : **−20 %** · 3+ : **−30 %** |
| **Remise bureau** | `Foyer.remiseManuelle` + `Inscription.remiseManuelle` |
| **Liste d’attente** | Non facturée jusqu’à confirmation bureau |
| **Extras** | Boutique & costumes **exclus** du total foyer |

```bash
php bin/console app:test-cotisation-calculator
php bin/console app:test-echelonnement
```

### 🎫 Capacité & liste d’attente

| Élément | Détail |
| :--- | :--- |
| **Capacité** | `Cours.capaciteMax` (défaut 25) |
| **Complet** | Proposition liste d’attente |
| **Âge** | `ageMin` / `ageMax` — front-office bloque ; EasyAdmin peut outrepasser |
| **Groupe** | `numeroGroupe` → affichage `getNomComplet()` (ex. `Modern Jazz #1`) |

### 🛠️ Administration (EasyAdmin)

Accès : **`ROLE_TRESORIER`** ou **`ROLE_BUREAU`**.

- Dashboard KPI
- Users, Foyers, Danseurs
- **Cours** — tarif, capacité, n° groupe, âge min/max, jauge, liste d’attente
- Inscriptions, Paiements
- **Goodies** / Achats boutique / Commandes
- Costumes, Réservations costumes
- Galas, Salles, Sponsors, Galerie, Flyer

---

## 🚀 Installation locale

### Prérequis

- PHP `>= 8.2` · Composer `2.x` · Git
- MySQL / MariaDB (ex. WAMP) ou PostgreSQL
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
DEFAULT_URI=http://localhost:8000

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

CLUB_IBAN="FR76 1234 5678 9012 3456 7890 123"
CLUB_BIC="ABCDFR21XXX"
CLUB_TITULAIRE="Studio Danse 430"

SOCIAL_FACEBOOK_URL="https://www.facebook.com/studiodanse430"
SOCIAL_INSTAGRAM_URL="#"
```

> Secrets de prod uniquement dans `.env.local` (non versionné).

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
| [/](http://localhost:8000) | Accueil |
| [/cours](http://localhost:8000/cours) | Planning semaine |
| [/boutique](http://localhost:8000/boutique) | Boutique |
| [/costumes](http://localhost:8000/costumes) | Location costumes |
| [/mon-foyer](http://localhost:8000/mon-foyer) | Espace familial |
| [/flyer](http://localhost:8000/flyer) | Flyer imprimable |
| [/espace-prof](http://localhost:8000/espace-prof) | Espace Prof |
| [/admin](http://localhost:8000/admin) | Back-office |

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
│   └── uploads/              # costumes/ · galerie/ · goodies/
├── src/
│   ├── Command/
│   ├── Controller/           # Public, Foyer, Boutique, Costume…
│   │   └── Admin/            # EasyAdmin
│   ├── Dto/
│   ├── Entity/               # + CommandeBoutique, Goodie, AchatGoodie…
│   ├── Enum/
│   ├── Form/
│   ├── Repository/
│   ├── Security/
│   └── Service/              # Cotisation, BoutiqueCart, Echelonnement…
├── templates/
│   ├── boutique/ · costume/ · cours/ · foyer/ · home/
│   ├── partials/             # _remarque_pedagogique.html.twig
│   └── base.html.twig
└── assets/
```

---

## 🗃️ Modèle de données (aperçu)

```text
User ── Foyer ── Danseur ── Inscription ── Cours
                              └── Paiement

Cours ── nom · numeroGroupe · jour · heure · professeur
         · dureeMinutes · tarif · capaciteMax
         · ageMin / ageMax · anneeNaissanceMin/Max

CommandeBoutique ── lignes (Goodie · taille · qty · prix)
AchatGoodie (historique foyer, legacy)
ReservationCostume ── Costume · dates · taille · statut · modePaiementSouhaite

Album ── Media     Gala ── Salle     Sponsor · Actualite
```

**Enums :** `StatutInscription` · `StatutPaiement` · `ModePaiement` · `StatutSante` · `StatutReservation` · `ModeLivraison` · `StatutCommandeBoutique` · `ModePaiementBoutique` · `ModeRetraitBoutique`

**Rôles :** `ROLE_USER` · `ROLE_PROF` · `ROLE_TRESORIER` · `ROLE_BUREAU`

---

## 📌 Notes métier

- Pas d’entité `Tarif` séparée : le prix vit sur **`Cours.tarif`**.
- Affichage public des disciplines : regroupement par **nom** (sans n° de groupe) sur l’accueil ; agenda détaillé sur `/cours`.
- Remarque pédagogique partagée : `templates/partials/_remarque_pedagogique.html.twig`.
- Le règlement **cours** est centralisé au foyer ; boutique et costumes ont leur propre confirmation.
- Libellé virement unique sur `Foyer.referenceVirement` (ex. `COTIS-2026-DUPONT`).
- RIB (`CLUB_*`) et réseaux (`SOCIAL_*`) exposés en globals Twig.

---

<div align="center">

⚫🟡 **Studio Danse 430** — _École de danse depuis 1976_

Fait avec ❤️ et Symfony 7.4

</div>
