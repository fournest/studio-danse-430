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

- 🎭 **Un site vitrine public** — Présentation de l'école, du planning des cours, de l'événement Gala et tunnel d'inscription en ligne pour les familles.
- 🛠️ **Un back-office d'administration** — Espace sécurisé (thème **Noir & Or** premium) permettant à l'équipe de gérer les adhérents, les cours, les inscriptions, les salles et les galas.

L'interface arbore une identité visuelle forte et cohérente : fond **noir profond**, accents **or (`#FFD700`)**, typographie marquée et animations soignées, aussi bien sur le site public que dans le tableau de bord d'administration.

---

## 🧱 Stack technique

| Domaine | Technologie | Détails |
| :--- | :--- | :--- |
| 🐘 **Langage** | PHP `8.2+` | Typage strict, enums, attributs |
| 🎼 **Framework** | Symfony `7.4` | Architecture MVC, AssetMapper, Security, Mailer, Notifier |
| 🖋️ **Templating** | Twig `3` | Templates du site vitrine et du back-office |
| 🗄️ **ORM** | Doctrine ORM `3` + Migrations | Entités, relations, migrations versionnées |
| 🔐 **Back-office** | EasyAdmin `5` | CRUD, tableau de bord, menu personnalisé en français |
| 🛡️ **Sécurité** | Symfony Security | `UserChecker` (compte actif + email vérifié), `login_throttling` (anti brute-force), hiérarchie `ROLE_PROF` / `ROLE_TRESORIER` / `ROLE_BUREAU` |
| 🎨 **CSS (site public)** | Tailwind CSS `v4` | Via `symfonycasts/tailwind-bundle` (compilation locale) |
| 🎨 **CSS (admin)** | Tailwind CSS `v3` | Chargé via **CDN autonome** sans écraser le CSS natif d'EasyAdmin (`preflight: false`) |
| 📤 **Uploads** | VichUploaderBundle | Gestion des fichiers (certificats médicaux, etc.) |
| ⚡ **Front interactif** | Symfony UX (Turbo + Stimulus) | Navigation fluide sans rechargement |
| 🗃️ **Base de données** | PostgreSQL *(défaut)* / MySQL / MariaDB | Configurable via `DATABASE_URL` |
| 🎟️ **Billetterie** | Billetweb | Réservation des places de Gala |
| 💳 **Paiement** *(prévu)* | HelloAsso | Point d'intégration préparé pour les cotisations |
| 🔄 **Automatisation** *(prévu)* | n8n | Webhook prêt pour notifier les nouvelles inscriptions |

> 💡 **À noter sur Tailwind :** le back-office charge Tailwind v3 via CDN avec `corePlugins.preflight: false`. Cette configuration "autonome" permet d'utiliser les classes utilitaires Tailwind **par-dessus** le design natif d'EasyAdmin sans en casser les styles.

---

## ✨ Fonctionnalités clés

### 🌐 Côté public (site vitrine)

- 🏠 **Page d'accueil** immersive avec hero, présentation de l'école, aperçu des cours et **fil d'actualités** (publications Facebook / Instagram synchronisées).
- 🤝 **Bandeau de sponsors** — Carrousel défilant (effet *marquee* avec pause au survol) mettant en avant les partenaires de l'association.
- 📅 **Catalogue des cours** — Liste détaillée (jour, horaire, professeur, capacité) et fiche par cours.
- 📝 **Inscription des danseurs** — Formulaire permettant à un parent d'inscrire un ou plusieurs danseurs à plusieurs cours en une seule fois (statut dossier/paiement initialisés automatiquement).
- 👨‍👩‍👧 **Espace « Mon Foyer »** *(réservé aux membres connectés)* — Configuration du foyer familial (adresse, contact d'urgence), puis tableau de bord (`/mon-foyer`) pour **consulter, ajouter et modifier les danseurs du foyer**, avec validation des champs et contrôle d'accès strict (un parent ne peut éditer que les danseurs de son propre foyer).
- 👗 **Location de costumes** — Catalogue public (`/location-costumes`) présentant les costumes disponibles à la location (nom, taille, prix au week-end, exemplaires, photo et consignes).
- 🎟️ **Billetterie Gala intégrée** — Page de réservation du **Gala annuel à la salle de la Boissière-des-Landes**, avec redirection vers **Billetweb** (via l'identifiant d'événement `billetwebEventId`) et affichage des places disponibles.
- 🔐 **Authentification sécurisée** — Connexion / déconnexion, avec **vérification de l'email**, **suspension de compte** et **limitation des tentatives** (anti brute-force).

### 🛠️ Côté administration (EasyAdmin — thème Noir/Or)

> Accès réservé aux comptes **`ROLE_TRESORIER`** ou supérieurs (`ROLE_BUREAU` hérite de ce rôle).

- 📊 **Tableau de bord** avec indicateurs clés (KPI) : total danseurs, cours, inscriptions, dossiers en attente + tableau des dernières inscriptions.
- 👥 **Gestion des utilisateurs** (parents et membres de l'équipe : bureau, trésorier, prof).
- 🏠 **Gestion des foyers / familles** (coordonnées, rattachement au compte parent).
- 🕺 **Gestion des danseurs** rattachés à leur foyer.
- 🎵 **Gestion des cours** (professeur, jour, horaire, capacité, lien de groupe WhatsApp).
- 🗂️ **Gestion des inscriptions** avec suivi du **statut du dossier** (En attente / Incomplet / Validé) et du **statut de paiement**.
- ⭐ **Gestion des galas** (date, salle, places, identifiant Billetweb).
- 📍 **Gestion des salles**.
- 👗 **Gestion des costumes** (nom, taille, prix, stock, description et **upload de la photo** du costume).
- 🤝 **Gestion des sponsors** (nom, logo).

---

## 🚀 Installation locale

### ✅ Prérequis

- **PHP** `>= 8.2` (avec extensions `ctype`, `iconv`)
- **Composer** `2.x`
- **Un serveur de base de données** : PostgreSQL, MySQL ou MariaDB
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

# PostgreSQL (valeur par défaut du projet)
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/studio_danse_430?serverVersion=16&charset=utf8"

# MySQL / MariaDB (ex. environnement WAMP)
# DATABASE_URL="mysql://root:@127.0.0.1:3306/studio_danse_430?serverVersion=8.0.32&charset=utf8mb4"

# --- Billetterie Billetweb ---
BILLETWEB_API_KEY=ta_cle_api_ici
BILLETWEB_API_URL=https://api.billetweb.fr/v1

# --- Intégrations futures (optionnel) ---
HELLOASSO_CLIENT_ID=ton_client_id_ici
HELLOASSO_CLIENT_SECRET=ton_client_secret_ici
N8N_WEBHOOK_URL=https://ton-instance-n8n/webhook/studio-danse-430
N8N_WEBHOOK_TOKEN=ton_token_secret_ici
```

> ⚠️ Ne mettez **jamais** de secrets de production dans le fichier `.env` versionné. Utilisez toujours `.env.local`.

### 🗃️ 4. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 🧬 5. Lancer les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 👤 6. Créer un compte membre de l'équipe

Les comptes d'équipe se créent via la commande `app:create-staff` avec l'un des types : `bureau`, `tresorier` ou `prof`. Seuls **`tresorier`** et **`bureau`** peuvent accéder au back-office (`/admin`).

```bash
# Trésorier (accès admin)
php bin/console app:create-staff tresorier@studio430.fr "MotDePasseSolide!" tresorier 0612345678

# Bureau (accès admin + hérite des droits trésorier et prof)
php bin/console app:create-staff bureau@studio430.fr "MotDePasseSolide!" bureau 0612345678
```

### 🌱 7. (Optionnel) Charger un jeu de données de test

Crée 2 familles (dont une membre du bureau), 3 danseurs, 2 cours et 3 inscriptions :

```bash
php bin/console app:seed-test-family
```

> Identifiants de test (mot de passe commun : `Password123!`) :
> - Parent standard : `parent.test@studio430.fr`
> - Parent bureau (accès admin) : `bureau.parent@studio430.fr`

### 🤝 8. (Optionnel) Charger les sponsors depuis les logos

```bash
php bin/console app:seed-sponsors
```

### 🎨 9. Compiler le CSS Tailwind du site public

```bash
php bin/console tailwind:build
# ou, en mode surveillance pendant le développement :
php bin/console tailwind:build --watch
```

### ▶️ 10. Lancer le serveur local

```bash
# Avec la Symfony CLI (recommandé)
symfony server:start

# OU avec le serveur PHP intégré
php -S localhost:8000 -t public/
```

L'application est alors disponible :

- 🌐 **Site public** → [http://localhost:8000](http://localhost:8000)
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
├── config/                 # Configuration Symfony (packages, routes, services)
├── migrations/             # Migrations Doctrine versionnées
├── public/                 # Racine web (point d'entrée, images, assets compilés)
│   ├── images/             # Logo, visuels du studio & logos sponsors
│   └── uploads/costumes/   # Photos des costumes uploadées via l'admin
├── src/
│   ├── Command/            # Commandes CLI (create-staff, seed-test-family, seed-sponsors)
│   ├── Controller/         # Contrôleurs publics (Home, Inscription, Foyer, Costume…)
│   │   └── Admin/          # Contrôleurs EasyAdmin (Dashboard + CRUD, dont Costume)
│   ├── Entity/             # Entités Doctrine (User, Foyer, Danseur, Cours, Inscription, Gala, Salle, Costume, Sponsor, Actualite)
│   ├── Form/               # Types de formulaires (InscriptionType, DanseurType)
│   ├── Repository/         # Repositories Doctrine
│   └── Security/           # Authentification (LoginFormAuthenticator) & contrôle d'accès (UserChecker)
├── templates/
│   ├── admin/              # Templates du back-office (dashboard Noir/Or)
│   ├── home/               # Page d'accueil
│   ├── foyer/              # Espace « Mon Foyer » (liste + formulaire danseur)
│   ├── costume/            # Catalogue de location de costumes
│   ├── sponsor/            # Bandeau défilant des partenaires
│   ├── cours/ gala/ ...    # Pages publiques
│   └── base.html.twig      # Layout principal
└── assets/                 # Sources front (CSS Tailwind, Stimulus, Turbo)
```

---

## 🗃️ Modèle de données (aperçu)

```text
User (parent / équipe) ── email + telephone + isVerified + isActif + roles
 └── 1,1 ── Foyer (nom, adresse, codePostal, ville, contactUrgence)
              └── 1,N ── Danseur (prenom, nom, dateNaissance)
                           └── N,N ── Cours
Inscription ── Danseur + Cours + Saison + StatutDossier + StatutPaiement
Gala ── Salle + dateHeure + placesDisponibles + billetwebEventId
Salle ── 1,N ── Gala
Costume ── nom + taille + prix + quantite + photo + description
Sponsor ── nom + logo
Actualite ── contenu + urlMedia + urlOrigine + plateforme + datePublication
```

- **`StatutDossier`** : `En attente` · `Incomplet` · `Validé`
- **`StatutPaiement`** : suivi du règlement des cotisations
- **`User.isVerified`** : l'email doit être validé pour se connecter
- **`User.isActif`** : un compte suspendu par l'association ne peut plus se connecter
- **Rôles d'équipe** : `ROLE_PROF` (professeur) · `ROLE_TRESORIER` (accès admin) · `ROLE_BUREAU` (hérite trésorier + prof)

---

<div align="center">

⚫🟡 **Studio Danse 430** — _École de danse depuis 1976_

Fait avec ❤️ et Symfony 7.4

</div>
