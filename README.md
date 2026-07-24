<div align="center">

# 🩰 Studio Danse 430

### _L'excellence de la danse depuis 1976_

Site vitrine & back-office de gestion pour une association de danse, habillé d'un thème élégant **Noir & Or** ⚫🟡

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

## 📖 Présentation du projet

**Studio Danse 430** est l'application web complète d'une **association de danse fondée en 1976**.
Le projet se compose de deux univers complémentaires :

- 🎭 **Un site vitrine public** — Présentation de l'école, planning des cours, galerie photos/médias, Gala, location de costumes et tunnel d'inscription pour les familles.
- 💃 **Un espace professeur** — Tableau de bord dédié (`ROLE_PROF`) pour consulter ses cours et la liste des élèves inscrits.
- 🛠️ **Un back-office d'administration** — Espace sécurisé (thème **Noir & Or** premium) pour gérer adhérents, cours, inscriptions, salles, galas, costumes et galerie.

L'interface arbore une identité visuelle forte et cohérente : fond **noir profond**, accents **or (`#FFD700`)**, typographie marquée et animations soignées, aussi bien sur le site public que dans le tableau de bord d'administration.

---

## 🧱 Stack technique

| Domaine | Technologie | Détails |
| :--- | :--- | :--- |
| 🐘 **Langage** | PHP `8.2+` | Typage strict, enums, attributs |
| 🎼 **Framework** | Symfony `7.4` | Architecture MVC, AssetMapper, Security, Mailer, RateLimiter |
| 🖋️ **Templating** | Twig `3` | Templates du site vitrine et du back-office |
| 🗄️ **ORM** | Doctrine ORM `3` + Migrations | Entités, relations, migrations versionnées |
| 🔐 **Back-office** | EasyAdmin `5` | CRUD, tableau de bord, menu personnalisé en français |
| 🛡️ **Sécurité** | Symfony Security + bundles | `UserChecker` (compte actif + email vérifié), `login_throttling` (anti brute-force), **vérification d'e-mail** (`symfonycasts/verify-email-bundle`), **mot de passe oublié** (`symfonycasts/reset-password-bundle`), hiérarchie `ROLE_PROF` / `ROLE_TRESORIER` / `ROLE_BUREAU` |
| 🎨 **CSS (site public)** | Tailwind CSS `v4` | Via `symfonycasts/tailwind-bundle` (compilation locale) |
| 🎨 **CSS (admin)** | Tailwind CSS `v3` | Chargé via **CDN autonome** sans écraser le CSS natif d'EasyAdmin (`preflight: false`) |
| 📤 **Uploads** | EasyAdmin `ImageField` + **VichUploaderBundle** | Costumes (`public/uploads/costumes/`) · Galerie (`public/uploads/galerie/` via mapping `galerie_images`) |
| ⚡ **Front interactif** | Symfony UX (Turbo + Stimulus) | Navigation fluide sans rechargement |
| 🗃️ **Base de données** | PostgreSQL *(défaut)* / MySQL / MariaDB | Configurable via `DATABASE_URL` ; Postgres fourni via Docker Compose |
| 🎟️ **Billetterie** | Billetweb | Lien de réservation Gala via `billetwebEventId` |
| 🔄 **Automatisation** | n8n | Webhook déclenché à chaque nouvelle inscription (si `N8N_WEBHOOK_URL` est défini) |
| 💳 **Paiement** *(prévu)* | HelloAsso | Champ `helloAssoPaymentId` + variables d'environnement préparés ; checkout non branché |

> 💡 **À noter sur Tailwind :** le back-office charge Tailwind v3 via CDN avec `corePlugins.preflight: false`. Cette configuration "autonome" permet d'utiliser les classes utilitaires Tailwind **par-dessus** le design natif d'EasyAdmin sans en casser les styles.

---

## ✨ Fonctionnalités clés

### 🌐 Côté public (site vitrine)

- 🏠 **Page d'accueil** immersive avec hero, présentation de l'école, aperçu des cours, lien vers la **galerie** et **fil d'actualités** (entité `Actualite` : contenu, média, plateforme, date).
- 🖼️ **Galerie photos & médias** — Albums publics (`/galerie`, `/galerie/{id}`) : images locales, embeds Instagram / Facebook / YouTube, légendes.
- 🤝 **Bandeau de sponsors** — Carrousel défilant (effet *marquee* avec pause au survol) mettant en avant les partenaires de l'association (nom, logo, lien optionnel).
- 📅 **Catalogue des cours** — Liste détaillée (jour, horaire, professeur, capacité) et fiche par cours (`/cours`, `/cours/{id}`).
- 👤 **Création de compte** — Inscription parent (`/register`) avec **vérification de l'e-mail** (`/verify/email`) obligatoire avant connexion.
- 👨‍👩‍👧 **Tunnel d'inscription familial** — Parcours guidé pour les familles connectées :
  1. Configurer le **foyer** (`/mon-foyer/configuration`)
  2. Ajouter un ou plusieurs **danseurs**
  3. Inscrire un danseur à un ou plusieurs **cours** (`/inscription`) — statut dossier / paiement initialisés automatiquement (`En attente` / `Non payé`)
  4. Consultation et suivi depuis l'espace **Mon Foyer**
- 👨‍👩‍👧 **Espace « Mon Foyer »** *(réservé aux membres connectés)* — Tableau de bord (`/mon-foyer`) pour consulter, ajouter et modifier les danseurs du foyer, avec contrôle d'accès strict. **Gestion du compte** : désactivation (déconnexion immédiate) ou **suppression définitive** (cascade foyer + danseurs).
- 💃 **Espace Professeur** *(réservé à `ROLE_PROF`)* — `/espace-prof` : liste des cours du professeur (filtre sur l'e-mail) et tableau des élèves inscrits (nom, date de naissance, contact responsable, case présence).
- 📱 **Navigation responsive** — En-tête et pied de page adaptés au mobile.
- 👗 **Location de costumes** — Catalogue public (`/location-costumes`) : nom, taille, prix au week-end, exemplaires, photo et consignes. Les membres connectés peuvent **réserver** un costume (`/location-costumes/{id}/reserver`) : dates de location, quantité, taille, mode de livraison (retrait aux locaux ou point relais), remarques. Le stock est décrémenté à la validation ; le statut initial est `En attente`.
- 🎟️ **Billetterie Gala** — Pages Gala (`/galas`, `/galas/reservation`) avec redirection vers **Billetweb** via `billetwebEventId`.
- 🚀 **Ticket support** *(équipe uniquement)* — Formulaire `/support-ticket` (`ROLE_BUREAU` / `ROLE_TRESORIER` / `ROLE_PROF`) envoyé par e-mail à **S1 Digital** (bug, amélioration, assistance ; pièce jointe image/PDF ≤ 10 Mo).
- 🔐 **Authentification sécurisée** — Connexion / déconnexion, vérification e-mail, suspension de compte, limitation des tentatives (anti brute-force).
- 🔑 **Mot de passe oublié** — Depuis `/login` → `/reset-password` : demande par e-mail, lien sécurisé à usage unique (token hashé en base, expiration automatique), puis saisie du nouveau mot de passe.

### 🛠️ Côté administration (EasyAdmin — thème Noir/Or)

> Accès réservé aux comptes **`ROLE_TRESORIER`** ou supérieurs (`ROLE_BUREAU` hérite de ce rôle). Les professeurs (`ROLE_PROF`) utilisent l'**Espace Prof**, pas le back-office.

- 📊 **Tableau de bord** avec indicateurs clés (KPI) : total danseurs, cours, inscriptions, dossiers en attente + tableau des dernières inscriptions.
- 👥 **Gestion des utilisateurs** (parents et membres de l'équipe : bureau, trésorier, prof) — Foyer rattaché, bascule **E-mail vérifié** / **Compte actif**, action **Bannir / Débannir**, suppression avec confirmation.
- 🏠 **Gestion des foyers / familles** (coordonnées, rattachement au compte parent).
- 🕺 **Gestion des danseurs** rattachés à leur foyer.
- 🎵 **Gestion des cours** (professeur, jour, horaire, capacité, lien de groupe WhatsApp).
- 🗂️ **Gestion des inscriptions** avec suivi du **statut du dossier** et du **statut de paiement**, saison, certificat médical (texte), mode de paiement, ID HelloAsso.
- ⭐ **Gestion des galas** (date, salle, places, identifiant Billetweb).
- 📍 **Gestion des salles**.
- 👗 **Gestion des costumes** (nom, taille, prix, stock, description et **upload de la photo**).
- 🛒 **Gestion des réservations de costumes** — Suivi des demandes (costume, demandeur, dates, quantité, mode de livraison, prix total) et changement de **statut** (`En attente` · `Validée` · `Refusée` · `Restituée` · `Annulée`).
- 🤝 **Gestion des sponsors** (nom, logo, lien).
- 🖼️ **Galerie & communication** — CRUD **Albums** (titre, date, description + collection de médias) et **Médias** (type, image VichUploader ou URL d'embed, légende).

---

## 🚀 Installation locale

### ✅ Prérequis

- **PHP** `>= 8.2` (avec extensions `ctype`, `iconv`)
- **Composer** `2.x`
- **Un serveur de base de données** : PostgreSQL, MySQL ou MariaDB
- **Docker** *(optionnel)* — pour démarrer PostgreSQL via `compose.yaml`
- **Symfony CLI** *(recommandé)* — [télécharger ici](https://symfony.com/download)
- **Git**

### 📥 1. Cloner le dépôt

```bash
git clone <url-du-depot> studio-danse-430
cd studio-danse-430
```

### 📦 2. Installer les dépendances

```bash
composer install
```

### ⚙️ 3. Configurer l'environnement

Créez un fichier `.env.local` à la racine pour y placer vos paramètres locaux (ce fichier n'est pas versionné) :

```dotenv
# Environnement
APP_ENV=dev
APP_SECRET=changez_moi_par_une_chaine_aleatoire

# --- Base de données ---
# Choisissez UNE des lignes ci-dessous selon votre SGBD :

# PostgreSQL (aligné sur Docker Compose : user/db = app)
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

# MySQL / MariaDB (ex. environnement WAMP)
# DATABASE_URL="mysql://root:@127.0.0.1:3306/studio_danse_430?serverVersion=8.0.32&charset=utf8mb4"

# --- Mailer (vérification d'e-mail & mot de passe oublié) ---
# En local, le transport "null" suffit pour ne pas envoyer de vrais e-mails :
MAILER_DSN=null://null
# Exemple SMTP (Mailtrap, etc.) :
# MAILER_DSN=smtp://user:pass@smtp.exemple.com:587

# --- Billetterie Billetweb ---
BILLETWEB_API_KEY=ta_cle_api_ici
BILLETWEB_API_URL=https://api.billetweb.fr/v1

# --- Automatisation n8n (optionnel) ---
N8N_WEBHOOK_URL=https://ton-instance-n8n/webhook/studio-danse-430
N8N_WEBHOOK_TOKEN=ton_token_secret_ici

# --- Paiement HelloAsso (prévu, optionnel) ---
HELLOASSO_CLIENT_ID=ton_client_id_ici
HELLOASSO_CLIENT_SECRET=ton_client_secret_ici
HELLOASSO_API_URL=https://api.helloasso.com
```

> ⚠️ Ne mettez **jamais** de secrets de production dans le fichier `.env` versionné. Utilisez toujours `.env.local`.
>
> 💡 Les flux **vérification d'e-mail** et **mot de passe oublié** nécessitent un `MAILER_DSN` fonctionnel pour recevoir les liens. En `dev`, consultez aussi le profiler Symfony (`/_profiler`) pour intercepter les e-mails si le transport est configuré en conséquence.

### 🐳 4. (Optionnel) Démarrer PostgreSQL avec Docker

```bash
docker compose up -d
```

Cela lance PostgreSQL 16 (utilisateur / base / mot de passe par défaut : `app` / `app` / `!ChangeMe!`).

### 🗃️ 5. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 🧬 6. Lancer les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 👤 7. Créer un compte membre de l'équipe

Les comptes d'équipe se créent via la commande `app:create-staff` avec l'un des types : `bureau`, `tresorier` ou `prof`. Seuls **`tresorier`** et **`bureau`** peuvent accéder au back-office (`/admin`).

```bash
# Trésorier (accès admin)
php bin/console app:create-staff tresorier@studio430.fr "MotDePasseSolide!" tresorier 0612345678

# Bureau (accès admin + hérite des droits trésorier et prof)
php bin/console app:create-staff bureau@studio430.fr "MotDePasseSolide!" bureau 0612345678

# Professeur (accès Espace Prof uniquement — l'e-mail doit correspondre au champ « professeur » des cours)
php bin/console app:create-staff prof@studio430.fr "MotDePasseSolide!" prof 0612345678
```

### 🌱 8. (Optionnel) Charger un jeu de données de test

Crée 2 familles (dont une membre du bureau), 3 danseurs, 2 cours et 3 inscriptions :

```bash
php bin/console app:seed-test-family
```

> Identifiants de test (mot de passe commun : `Password123!`) :
> - Parent standard : `parent.test@studio430.fr`
> - Parent bureau (accès admin) : `bureau.parent@studio430.fr`

### 🤝 9. (Optionnel) Charger les sponsors depuis les logos

```bash
php bin/console app:seed-sponsors
```

### 🎨 10. Compiler le CSS Tailwind du site public

```bash
php bin/console tailwind:build
# ou, en mode surveillance pendant le développement :
php bin/console tailwind:build --watch
```

### ▶️ 11. Lancer le serveur local

```bash
# Avec la Symfony CLI (recommandé)
symfony server:start

# OU avec le serveur PHP intégré
php -S localhost:8000 -t public/
```

L'application est alors disponible :

- 🌐 **Site public** → [http://localhost:8000](http://localhost:8000)
- 🖼️ **Galerie** → [http://localhost:8000/galerie](http://localhost:8000/galerie)
- 💃 **Espace Prof** → [http://localhost:8000/espace-prof](http://localhost:8000/espace-prof)
- 🔐 **Back-office** → [http://localhost:8000/admin](http://localhost:8000/admin)

---

## 🧰 Commandes utiles

### 🧹 Cache & maintenance

```bash
php bin/console cache:clear        # Vider le cache
php bin/console debug:router        # Lister toutes les routes
php bin/console debug:container      # Inspecter les services
php bin/console about                # Infos sur l'environnement Symfony
```

### 🗄️ Base de données & Doctrine

```bash
php bin/console make:migration                        # Générer une migration depuis les entités
php bin/console doctrine:migrations:migrate            # Appliquer les migrations
php bin/console doctrine:schema:validate               # Vérifier la cohérence schéma / entités
php bin/console doctrine:database:create               # Créer la BDD
php bin/console doctrine:database:drop --force         # Supprimer la BDD
```

### 🏗️ Génération de code (MakerBundle)

```bash
php bin/console make:entity        # Créer / modifier une entité
php bin/console make:controller     # Créer un contrôleur
php bin/console make:crud           # Générer un CRUD EasyAdmin
php bin/console make:form           # Créer un formulaire
```

### 🎨 Front-end

```bash
php bin/console tailwind:build --watch   # Compiler Tailwind (site public) en continu
php bin/console importmap:install         # Installer les assets de l'importmap
php bin/console asset-map:compile         # Compiler les assets pour la prod
```

### 🧑‍💼 Commandes métier (spécifiques au projet)

```bash
php bin/console app:create-staff <email> <password> <type> [telephone]  # Créer un membre d'équipe (bureau|tresorier|prof)
php bin/console app:seed-test-family                                    # Charger des données de test
php bin/console app:seed-sponsors                                       # Importer les sponsors depuis les logos
```

---

## 📂 Structure du projet

```text
studio-danse-430/
├── compose.yaml            # PostgreSQL 16 (Docker Compose)
├── config/                 # Configuration Symfony (packages, routes, services)
├── migrations/             # Migrations Doctrine versionnées
├── public/                 # Racine web (point d'entrée, images, assets compilés)
│   ├── images/             # Logo, visuels du studio & logos sponsors
│   └── uploads/
│       ├── costumes/       # Photos des costumes (admin)
│       └── galerie/        # Images de la galerie (VichUploader)
├── src/
│   ├── Command/            # Commandes CLI (create-staff, seed-test-family, seed-sponsors)
│   ├── Controller/         # Publics (Home, Galerie, EspaceProf, Support, Costume, Foyer…)
│   │   └── Admin/          # EasyAdmin (Dashboard + CRUD dont Album / Media)
│   ├── Entity/             # Entités Doctrine (User, Foyer, Album, Media, Costume…)
│   ├── Enum/               # ModeLivraison, StatutReservation, TypeMedia
│   ├── Form/               # Formulaires (Inscription, CostumeReservation, Media, TicketSupport…)
│   ├── Model/              # DTO (SupportData)
│   ├── Repository/         # Repositories Doctrine
│   └── Security/           # Authentification & UserChecker
├── templates/
│   ├── admin/              # Back-office (dashboard Noir/Or)
│   ├── home/               # Page d'accueil
│   ├── galerie/            # Liste & détail des albums
│   ├── espace_prof/        # Espace professeur
│   ├── support/            # Formulaire ticket S1 Digital
│   ├── foyer/              # Espace « Mon Foyer »
│   ├── inscription/        # Tunnel d'inscription aux cours
│   ├── registration/       # Création de compte & e-mail de vérification
│   ├── costume/            # Catalogue + réservation de costumes
│   ├── reset_password/     # Mot de passe oublié
│   ├── security/           # Connexion
│   ├── sponsor/            # Bandeau partenaires
│   ├── cours/ gala/ ...    # Autres pages publiques
│   └── base.html.twig      # Layout principal
└── assets/                 # Sources front (CSS Tailwind, Stimulus, Turbo)
```

---

## 🗃️ Modèle de données (aperçu)

```text
User (parent / équipe) ── email + telephone + isVerified + isActif + roles
 └── 1,1 ── Foyer (nom, adresse, codePostal, ville, contactUrgence)  [orphanRemoval : cascade si User supprimé]
              └── 1,N ── Danseur (prenom, nom, dateNaissance)
                           └── N,N ── Cours (via Inscription)
Inscription ── Danseur + Cours + Saison + StatutDossier + StatutPaiement
               + certificatMedical + modePaiement + helloAssoPaymentId
ResetPasswordRequest ── User + expiresAt + selector + hashedToken
Gala ── Salle + dateHeure + placesDisponibles + billetwebEventId
Salle ── 1,N ── Gala
Costume ── nom + taille + prix + quantite + photo + description
ReservationCostume ── Costume + User + dates (événement / début / fin) + taille + quantite
                       + ModeLivraison + prixTotal + StatutReservation + remarques + createdAt
Album ── titre + description + dateEvenement + 1,N Media
Media ── TypeMedia + imageName (Vich) / embedUrl + legende + Album
Sponsor ── nom + logo + lien
Actualite ── contenu + urlMedia + urlOrigine + plateforme + datePublication
```

- **`StatutDossier`** : `En attente` · `Incomplet` · `Validé`
- **`StatutPaiement`** : `Non payé` · `Partiel` · `Soldé`
- **`StatutReservation`** : `En attente` · `Validée` · `Refusée` · `Restituée` · `Annulée`
- **`ModeLivraison`** : `retrait_locaux` (gratuit, Nieul-le-Dolent) · `point_relais` (sur devis)
- **`TypeMedia`** : `IMAGE_LOCAL` · `INSTAGRAM` · `FACEBOOK` · `YOUTUBE`
- **`User.isVerified`** : l'email doit être validé pour se connecter
- **`User.isActif`** : un compte suspendu (par l'association ou via « Désactiver mon compte ») ne peut plus se connecter
- **`ResetPasswordRequest`** : demande de réinitialisation de mot de passe (token hashé, expiration, lié à un `User`)
- **Rôles d'équipe** : `ROLE_PROF` (Espace Prof) · `ROLE_TRESORIER` (accès admin) · `ROLE_BUREAU` (hérite trésorier + prof)

---

<div align="center">

⚫🟡 **Studio Danse 430** — _École de danse depuis 1976_

Fait avec ❤️ et Symfony 7.4

</div>
