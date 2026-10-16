# API Jeux vidéo

API REST écrite « à la main » avec Symfony 8.1 (sans API Platform) pour gérer des jeux vidéo, leurs catégories, leurs éditeurs et les utilisateurs.
Elle comprend aussi une newsletter hebdomadaire qui liste les sorties des 7 prochains jours.

## Fonctionnalités

- CRUD complet pour les **jeux vidéo**, **catégories**, **éditeurs** et **utilisateurs**.
- Authentification **JWT** (LexikJWTAuthenticationBundle).
- Gestion des droits : tout utilisateur connecté peut consulter les ressources. Seul un `ROLE_ADMIN` peut créer, modifier ou supprimer, et gérer les utilisateurs.
- Validation des données (contraintes `Assert`) et erreurs renvoyées en JSON (`ExceptionSubscriber`).
- Pagination des listes avec `?page=` et `?limit=` (3 éléments par défaut).
- Jaquette (`coverImage`, une URL) sur les jeux.
- Abonnement à la newsletter (`subcriptionToNewsletter`) sur les utilisateurs.
- Commande `app:send-newsletter`, planifiée **tous les lundis à 8h30** (Europe/Paris) avec Symfony Scheduler.
- Documentation **Swagger** générée par NelmioApiDocBundle.

## Stack

| Élément | Version |
|---|---|
| PHP | ≥ 8.4 |
| Symfony | 8.1 |
| Base de données | MySQL 8.4 (Doctrine ORM 3 + migrations) |
| Auth | lexik/jwt-authentication-bundle 3 |
| Emails | Symfony Mailer + Twig (Mailtrap ou Mailpit en dev) |
| Tâches planifiées | Symfony Scheduler + Messenger |
| Documentation | nelmio/api-doc-bundle 5 |

## Installation

```bash
git clone <url-du-depot> api_games
cd api_games
composer install
```

### 1. Variables d'environnement

Le fichier `.env` (commité) contient uniquement des valeurs par défaut. Créez un `.env.local`, qui n'est pas commité :

```dotenv
DATABASE_URL="mysql://user:motdepasse@127.0.0.1:3306/api_games?serverVersion=8.4.0&charset=utf8mb4"
JWT_PASSPHRASE=votre_passphrase
MAILER_DSN=smtp://user:pass@sandbox.smtp.mailtrap.io:2525
# ou, avec le conteneur Mailpit de compose.override.yaml :
# MAILER_DSN=smtp://localhost:1025
```

### 2. Base de données et jeu de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
```

### 3. Clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

Les clés sont créées dans `config/jwt/` et ne sont pas commitées.

### 4. Lancer le serveur

```bash
symfony serve -d
```

## Comptes de test (fixtures)

| Email | Mot de passe | Rôle | Newsletter |
|---|---|---|---|
| `marco@exemple.fr` | `admin123` | `ROLE_ADMIN` | oui |
| `user@exemple.fr` | `user123` | `ROLE_USER` | non |

## Authentification

```bash
curl -X POST http://127.0.0.1:8000/api/login_check \
  -H 'Content-Type: application/json' \
  -d '{"username":"marco@exemple.fr","password":"admin123"}'
```

Envoyez ensuite le token reçu dans l'en-tête de chaque requête :

```
Authorization: Bearer <token>
```

Toutes les routes `/api/*` exigent un token, sauf `/api/login_check` et la documentation.

## Documentation Swagger

- Interface : **http://127.0.0.1:8000/api/doc**
- JSON OpenAPI : http://127.0.0.1:8000/api/doc.json

Dans l'interface, appelez `POST /api/login_check`, cliquez sur **Authorize** et collez le token : vous pouvez ensuite tester tous les endpoints.

## Endpoints

Préfixe : `/api/v1`. 🔒 = réservé à `ROLE_ADMIN`.

| Ressource | GET liste | GET détail | POST | PUT | DELETE |
|---|---|---|---|---|---|
| Jeux vidéo | `/games` | `/games/{id}` | 🔒 | 🔒 | 🔒 |
| Catégories | `/categories` | `/categories/{id}` | 🔒 | 🔒 | 🔒 |
| Éditeurs | `/editors` | `/editors/{id}` | 🔒 | 🔒 | 🔒 |
| Utilisateurs | 🔒 `/users` | 🔒 `/users/{id}` | 🔒 | 🔒 | 🔒 |

### Codes de retour

| Code | Cas |
|---|---|
| `200` | Lecture, modification (`{"status":"success"}` + en-tête `Location`), suppression (`{"status":"success"}`) |
| `201` | Création : renvoie la ressource, avec son URL dans l'en-tête `Location` |
| `400` | JSON invalide, erreur de validation, relation inexistante, suppression de son propre compte |
| `401` | Token absent, invalide ou expiré |
| `403` | Droits insuffisants (pas `ROLE_ADMIN`) |
| `404` | Ressource ou route introuvable |
| `409` | Suppression d'un éditeur qui possède encore des jeux |

Les erreurs ont la forme `{"status": 404, "message": "..."}`.

### Exemple : créer un jeu

Les relations sont passées par id : `idEditor` (obligatoire) et `idCategories` (tableau). En `PUT`, seuls les champs envoyés sont modifiés, et les catégories sont remplacées si `idCategories` est présent.

```bash
curl -X POST http://127.0.0.1:8000/api/v1/games \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "title": "Hollow Knight: Silksong",
    "releaseDate": "2026-10-20T00:00:00+02:00",
    "description": "Suite de Hollow Knight.",
    "coverImage": "https://exemple.fr/silksong.jpg",
    "idEditor": 1,
    "idCategories": [1, 2]
  }'
```

## Newsletter

### Envoi manuel

```bash
php bin/console app:send-newsletter
```

Elle envoie à chaque utilisateur abonné un email (template `templates/emails/newsletter.html.twig`) avec les jeux qui sortent entre aujourd'hui et J+7.

### Envoi planifié (lundi 8h30)

La commande porte l'attribut `#[AsCronTask('30 8 * * 1', timezone: 'Europe/Paris')]` et fait partie du schedule par défaut (`src/Schedule.php`).
Pour que l'envoi ait lieu, un worker doit consommer ce schedule :

```bash
php bin/console messenger:consume scheduler_default
```

En production, ce worker doit tourner en continu (Supervisor, systemd, ou `symfony run -d --watch=config,src php bin/console messenger:consume scheduler_default` en local).

Pour vérifier la planification et la prochaine exécution :

```bash
php bin/console debug:scheduler
```

## Structure du projet

```
src/
├── Command/SendNewsletterCommand.php   # newsletter + #[AsCronTask]
├── Controller/                          # un contrôleur par ressource (+ attributs OpenAPI)
├── DataFixtures/                        # jeu de données de test
├── Entity/                              # VideoGame, Category, Editor, User
├── EventSubscriber/ExceptionSubscriber.php  # erreurs en JSON
├── Repository/                          # pagination, abonnés, sorties à venir
└── Schedule.php                         # schedule par défaut (Scheduler)
templates/emails/newsletter.html.twig
```
