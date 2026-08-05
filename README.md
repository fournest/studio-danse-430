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

- 🎭 **Site vitrine** — Accueil, planning, galerie, Gala, sponsors, actualités
- 👨‍👩‍👧 **Inscriptions familles** — Tunnel foyer (santé, cotisation, échéancier, déclaration de paiement)
- 👕 **Boutique** — Goodies & vêtements (tunnel séparé)
- 👗 **Location costumes** — Tunnel hors-gala
- 💃 **Espace professeur** — Cours et élèves (`ROLE_PROF`)
- 🖨️ **Flyers** — Génération / impression A5
- 🛠️ **Back-office EasyAdmin** — Adhérents, règlements, LDC, livrets dynamiques, galerie

Identité visuelle : fond **noir**, accents **or (`#FFD700`)**.

| Tunnel | Portée | Paiement |
| :--- | :--- | :--- |
| **1 · Foyer** `/mon-foyer` | Cours, cotisation, santé, échéancier | Chèque / virement / Pass'Sport / ANCV / HelloAsso |
| **2 · Boutique** `/boutique` | Goodies & vêtements | 1× (HelloAsso / CB, chèque ou espèces) |
| **3 · Costumes** `/costumes` | Location hors-gala | 1× + caution au retrait |

---

## 🧱 Stack technique

| Domaine | Technologie |
| :--- | :--- |
| PHP `8.2+` · Symfony `7.4` · Twig `3` |
| Doctrine ORM `3` + **Migrations** (schéma versionné) |
| EasyAdmin `5` · Tailwind CSS `v4` (`symfonycasts/tailwind-bundle`) |
| Symfony Security · Verify Email · Reset Password |
| VichUploader · Dompdf (livrets PDF) · Symfony Mailer |
| MySQL / MariaDB / PostgreSQL via `DATABASE_URL` |

---

## 🔐 Comptes & authentification

Deux parcours distincts :

| Parcours | URL | Usage |
| :--- | :--- | :--- |
| **Première connexion** (adhérents importés) | `/activation` | E-mail pré-enregistré, pas de mot de passe → lien 48 h → création MDP → Espace Famille |
| **Inscription classique** (nouveaux membres) | `/register` | Prénom, nom, e-mail, MDP sécurisé → e-mail de vérification → création du foyer |

Règles mot de passe (activation + inscription) : **8 caractères min.**, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.

Connexion : `/login` · Mot de passe oublié : `/reset-password`

**Import adhérents :** créer les comptes avec `is_activated = 0` et `password = NULL` pour forcer le parcours `/activation`.

---

## ✨ Fonctionnalités clés (récentes)

- **Solde foyer** — Total dû, déclaré par la famille, encaissé, reste à payer (badges)
- **Déclaration paiement famille** — Modale « Signaler un règlement effectué » → statut `paiement_declare` → validation trésorerie
- **Règlements EasyAdmin** — Synthèse par inscription, échéancier, relance retard (> 5 j)
- **Livrets dynamiques** — `/admin/doc/voir/{type}` + PDF à la volée (Dompdf)
- **LDC** — Upload PDF, déclaration en vigueur
- **Relances e-mail** — Pièces manquantes · retard de paiement

---

## 🚀 Installation locale

### Prérequis

- PHP `>= 8.2` · Composer `2.x` · Git
- MySQL / MariaDB (ex. WAMP) ou PostgreSQL
- Symfony CLI *(recommandé)*

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

DATABASE_URL="mysql://root:@127.0.0.1:3306/studio_danse_430?serverVersion=8.0.32&charset=utf8mb4"

MAILER_DSN=null://null
# En dev sans envoi réel : null://null
# SMTP : smtp://user:pass@smtp.example.com:587

HELLOASSO_CAMPAIGN_URL=
CLUB_IBAN="FR76 ..."
CLUB_BIC="ABCDFR21XXX"
CLUB_TITULAIRE="Studio Danse 430"
SOCIAL_FACEBOOK_URL="https://www.facebook.com/studiodanse430"
SOCIAL_INSTAGRAM_URL="#"
```

### 3. Base de données — **obligatoire**

Le schéma est géré uniquement par les **migrations Doctrine** (pas de `schema:update` en prod).

```bash
# Créer la base (si elle n'existe pas)
php bin/console doctrine:database:create

# Appliquer toutes les migrations
php bin/console doctrine:migrations:migrate
```

**Après chaque `git pull`** qui ajoute des fichiers dans `migrations/`, relancer :

```bash
php bin/console doctrine:migrations:migrate
```

Vérifier l'état :

```bash
php bin/console doctrine:migrations:status
# « New » doit être 0 et « Current » = « Latest »
```

> Si `migrate` n'a pas été exécuté, l'application peut planter (colonnes ou tables manquantes : `is_activated`, `account_activation_token`, `date_declaration` sur `paiement`, etc.).

### 4. Comptes équipe

```bash
php bin/console app:promote-user president@studio430.fr president
php bin/console app:create-staff tresorier@studio430.fr "MotDePasseSolide!" tresorier 0612345678
php bin/console app:create-staff prof@studio430.fr "MotDePasseSolide!" prof 0612345678
```

### 5. Données de test *(optionnel)*

```bash
php bin/console app:seed-test-family
php bin/console app:seed-sponsors
```

### 6. CSS & serveur

```bash
php bin/console tailwind:build
symfony server:start
# ou : php -S localhost:8000 -t public/
```

| URL | Rôle |
| :--- | :--- |
| [/](http://localhost:8000) | Accueil |
| [/login](http://localhost:8000/login) | Connexion |
| [/activation](http://localhost:8000/activation) | Première connexion (import) |
| [/register](http://localhost:8000/register) | Inscription nouveau membre |
| [/mon-foyer](http://localhost:8000/mon-foyer) | Espace familial |
| [/boutique](http://localhost:8000/boutique) | Boutique |
| [/costumes](http://localhost:8000/costumes) | Costumes |
| [/admin](http://localhost:8000/admin) | Back-office |

---

## 🧰 Commandes utiles

```bash
# Métier
php bin/console app:promote-user <email> <role>
php bin/console app:seed-test-family
php bin/console app:test-cotisation-calculator
php bin/console app:test-echelonnement

# Doctrine — après chaque mise à jour du dépôt
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate

# Cache / debug
php bin/console cache:clear
php bin/console debug:router

# Front
php bin/console tailwind:build --watch
```

---

## 📂 Structure (aperçu)

```text
src/
├── Controller/
│   ├── ActivationController.php      # /activation
│   ├── RegistrationController.php    # /register + verify email
│   ├── FoyerController.php           # Espace famille
│   └── Admin/                        # EasyAdmin CRUD
├── Entity/                           # User, Foyer, Paiement, Inscription…
├── Service/                          # Cotisation, déclaration paiement, livrets PDF…
├── Security/                         # Login, UserChecker, EmailVerifier
└── Validator/Constraints/StrongPassword.php

templates/
├── security/                         # login, activation_request, activation_reset
├── foyer/                            # Espace famille
├── emails/                           # activation_account, relances…
└── admin/documentation/              # Livrets dynamiques
```

---

## 🗃️ Rôles Symfony

Hiérarchie : `ROLE_PRESIDENCE` → `ROLE_BUREAU` → `ROLE_PROF` → `ROLE_USER`

Rôles stockés : `ROLE_PRESIDENT`, `ROLE_VICE_PRESIDENT`, `ROLE_TRESORIER`, `ROLE_SECRETAIRE`, `ROLE_PROF`, etc.

---

## 📌 Notes métier

- Cotisation cours : `CotisationCalculatorService` — saison **2026/2027**
- Statuts ligne paiement : `en_attente` · `paiement_declare` · `encaisse` · `retard` (affichage)
- Reste à payer foyer = total dû − **encaissé** (déclaré non déduit tant que non validé)
- Livrets admin : générés en Twig + PDF (pas de fichiers statiques à maintenir)

---

<div align="center">

⚫🟡 **Studio Danse 430** — _École de danse depuis 1976_

Fait avec ❤️ et Symfony 7.4

</div>
