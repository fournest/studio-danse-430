<div align="center">

# 🩰 Studio Danse 430

### _L'excellence de la danse depuis 1976_

Site vitrine & back-office de gestion pour une association de danse — identité **Noir & Or** ⚫🟡

<br>

![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=for-the-badge&logo=symfony&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Twig](https://img.shields.io/badge/Twig-3-1E1E1E?style=for-the-badge&logo=twig&logoColor=white)
![EasyAdmin](https://img.shields.io/badge/EasyAdmin-5-2D9CDB?style=for-the-badge)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v3/v4-38BDF8?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Doctrine](https://img.shields.io/badge/Doctrine_ORM-3-FC6A31?style=for-the-badge)
![Licence](https://img.shields.io/badge/Licence-Propriétaire-red?style=for-the-badge)

</div>

---

## 📖 Présentation

**Studio Danse 430** est l'application web d'une **association de danse fondée en 1976**. Elle couvre :

- 🎭 **Site vitrine** — Accueil, planning des cours (avec tarifs saison), galerie, Gala, costumes, inscription familiale
- 👨‍👩‍👧 **Espace familles** — Foyer, danseurs, familles recomposées (2ᵉ parent), choix des cours & calculateur de cotisations
- 💃 **Espace professeur** — Cours et élèves du prof (`ROLE_PROF`)
- 🛠️ **Back-office EasyAdmin** — Adhérents, cours/tarifs, inscriptions, remises bureau, galas, costumes, galerie

Identité visuelle : fond **noir**, accents **or (`#FFD700`)**, typographie marquée.

---

## 🧱 Stack technique

| Domaine | Technologie | Détails |
| :--- | :--- | :--- |
| 🐘 **Langage** | PHP `8.2+` | Typage strict, enums, attributs |
| 🎼 **Framework** | Symfony `7.4` | MVC, AssetMapper, Security, Mailer, RateLimiter |
| 🖋️ **Templating** | Twig `3` | Site public + admin |
| 🗄️ **ORM** | Doctrine ORM `3` + Migrations | Entités, relations, migrations |
| 🔐 **Back-office** | EasyAdmin `5` | CRUD, dashboard, menu FR |
| 🛡️ **Sécurité** | Symfony Security | `UserChecker`, login throttling, verify-email, reset-password, rôles `ROLE_PROF` / `ROLE_TRESORIER` / `ROLE_BUREAU` |
| 🎨 **CSS public** | Tailwind CSS `v4` | `symfonycasts/tailwind-bundle` |
| 🎨 **CSS admin** | Tailwind CSS `v3` CDN | `preflight: false` (ne casse pas EasyAdmin) |
| 📤 **Uploads** | EasyAdmin + VichUploader | Costumes · Galerie |
| ⚡ **Front** | Symfony UX (Turbo + Stimulus) | |
| 🗃️ **BDD** | MySQL / MariaDB / PostgreSQL | Via `DATABASE_URL` ; Postgres optionnel (Docker) |
| 🎟️ **Billetterie** | Billetweb | Gala via `billetwebEventId` |
| 🔄 **Automatisation** | n8n | Webhook inscriptions (`N8N_WEBHOOK_URL`) |
| 💳 **Paiement** *(prévu)* | HelloAsso | Champs & env prêts ; checkout non branché |

---

## ✨ Fonctionnalités clés

### 🌐 Site public

- 🏠 **Accueil** — Hero, aperçu des cours **avec tarifs**, actualités, sponsors (marquee)
- 📅 **Cours** — `/cours`, `/cours/{id}` : jour, horaire, durée, professeur, capacité, **tarif saison** (affiché si > 0 €)
- 🖼️ **Galerie** — Albums & médias (local / Instagram / Facebook / YouTube)
- 👗 **Costumes** — Catalogue & réservation (membres connectés)
- 🎟️ **Gala** — Réservation via Billetweb
- 👤 **Compte** — Inscription + vérification e-mail, login sécurisé, mot de passe oublié
- 🎫 **Support** — Ticket interne S1 Digital (`ROLE_BUREAU` / `TRESORIER` / `PROF`)

### 👨‍👩‍👧 Espace familial (`ROLE_USER`)

Parcours guidé :

1. **Configurer le foyer** — `/mon-foyer/configuration`
2. **Ajouter des danseurs** — `/mon-foyer/ajouter-un-danseur`
3. **Choisir les cours** — `/mon-foyer/inscription-cours`  
   - Cases à cocher **par danseur**  
   - Filtrage des créneaux par **année de naissance**  
   - **Récapitulatif cotisation** (sous-total, gratuité 2020, remise foyer, remise bureau, total net)  
   - Persistance des `Inscription` + sync ManyToMany danseur↔cours  
4. Suivi dans **Mon Foyer** (`/mon-foyer`) — fiches danseurs, désactivation / suppression de compte

**Familles recomposées :**
- Parent 2 au niveau **Foyer** et/ou **Danseur** (email, téléphone, nom)
- Le 2ᵉ parent connecté avec le même e-mail voit les fiches en **lecture seule**
- Titulaire seul : inscriptions & modifications

> L’ancienne URL `/inscription` redirige vers `/mon-foyer/inscription-cours`.

### 💶 Cotisations — saison 2026/2027

Service métier : `CotisationCalculatorService` (`src/Service/`).

| Règle | Détail |
| :--- | :--- |
| **Tarif de base** | Champ `Cours.tarif` (modifié en EasyAdmin) |
| **Gratuité 2020** | Enfant né en **2020** avec ≥ 2 cours → le **moins cher** est gratuit |
| **Remise foyer** | 1 cours payant : 0 % · 2 cours : **−20 %** · 3+ : **−30 %** |
| **Remise bureau** | `Foyer.remiseManuelle` + `Inscription.remiseManuelle` (motif optionnel) déduites du total |

Validation CLI des scénarios Excel :

```bash
php bin/console app:test-cotisation-calculator
```

### 💃 Espace Professeur (`ROLE_PROF`)

`/espace-prof` — cours du professeur (filtre e-mail) et liste des élèves.

### 🛠️ Administration (EasyAdmin)

Accès : **`ROLE_TRESORIER`** ou **`ROLE_BUREAU`**.

- Dashboard KPI (danseurs, cours, inscriptions, dossiers en attente)
- Users, Foyers (dont **remise manuelle** + motif), Danseurs (parent 2)
- **Cours** — durée, **tarif (€)**, bornes d’années de naissance, WhatsApp
- **Inscriptions** — statuts dossier/paiement, remise manuelle, HelloAsso
- Galas, Salles, Costumes, Réservations, Sponsors, Albums / Médias

---

## 🚀 Installation locale

### Prérequis

- PHP `>= 8.2` · Composer `2.x` · Git
- MySQL / MariaDB / PostgreSQL
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

N8N_WEBHOOK_URL=
N8N_WEBHOOK_TOKEN=

HELLOASSO_CLIENT_ID=
HELLOASSO_CLIENT_SECRET=
HELLOASSO_API_URL=https://api.helloasso.com
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
# Bureau : bureau.parent@studio430.fr / Password123!

php bin/console app:test-blended-family
# Titulaire : chloe.parent@studio430.fr
# 2ᵉ parent : mathieu.parent@studio430.fr  (lecture seule sur Léa)

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
| [http://localhost:8000/mon-foyer/inscription-cours](http://localhost:8000/mon-foyer/inscription-cours) | Choix des cours + cotisation |
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
│   ├── Command/          # create-staff, seeds, test-cotisation, test-blended-family
│   ├── Controller/       # Public + Foyer (inscription-cours)
│   │   └── Admin/        # EasyAdmin CRUD
│   ├── Dto/              # CotisationDetail, breakdowns
│   ├── Entity/           # User, Foyer, Danseur, Cours, Inscription…
│   ├── Enum/
│   ├── Form/
│   ├── Model/
│   ├── Repository/
│   ├── Security/
│   └── Service/          # CotisationCalculatorService
├── templates/
│   ├── admin/ · home/ · cours/ · foyer/
│   ├── galerie/ · costume/ · gala/ · security/ …
│   └── base.html.twig
└── assets/
```

---

## 🗃️ Modèle de données (aperçu)

```text
User ── email · telephone · isVerified · isActif · roles
 └── 1,1 ── Foyer
              · coordonnées · parent2* · remiseManuelle · motifRemise
              └── 1,N ── Danseur
                           · identité · dateNaissance · parent2* (override)
                           └── N,N ── Cours          (danseur_cours)
                           └── 1,N ── Inscription ── Cours
                                        · saison · statuts · payeur*
                                        · remiseManuelle · motifRemise

Cours ── nom · jour · heure · professeur · capaciteMax
         · dureeMinutes · tarif · anneeNaissanceMin/Max · whatsapp

Album ── 1,N Media     Costume ── ReservationCostume     Gala ── Salle
Sponsor · Actualite · ResetPasswordRequest
```

**Enums :** `StatutDossier` · `StatutPaiement` · `StatutReservation` · `ModeLivraison` · `TypeMedia`

**Rôles :** `ROLE_USER` · `ROLE_PROF` · `ROLE_TRESORIER` · `ROLE_BUREAU` (hérite trésorier + prof)

---

## 📌 Notes métier cotisations

- Pas d’entité `Tarif` séparée : le prix vit sur **`Cours.tarif`** (grille admin).
- Les tarifs publics (accueil / `/cours`) ne s’affichent que si `tarif > 0`.
- Après inscription, le bureau peut ajuster via **remise manuelle** foyer ou inscription.

---

<div align="center">

⚫🟡 **Studio Danse 430** — _École de danse depuis 1976_

Fait avec ❤️ et Symfony 7.4

</div>
