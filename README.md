# ABIWEIN

ABIWEIN est une application web de gestion d'exploitation avicole. Elle centralise les opérations de la ferme, leur suivi et les indicateurs utiles à la décision dans une interface française, responsive et adaptée à un usage quotidien.

Le socle technique est installé et l'espace visiteur est structuré en pages Blade distinctes : accueil, présentation, contact et entrée de l'espace privé. Les modules métier seront ajoutés par étapes sur cette fondation.

## Décisions fonctionnelles établies

- Les règlements sont effectués **uniquement en espèces**.
- Une vente est conclue **au comptant** ou **à crédit**. Une créance peut ensuite être réglée par un ou plusieurs paiements en espèces.
- Les alertes sont **internes à l'application** : aucun SMS, e-mail ou service tiers n'est requis dans le socle initial.
- L'interface est en français et doit fonctionner sur ordinateur, tablette et téléphone.
- Le dépôt distant du projet doit être **privé sur GitHub**.

## Socle technique

| Élément | Version de référence | Remarque |
|---|---:|---|
| PHP | 8.4.15 | Installation WampServer vérifiée avec les extensions requises |
| Composer | 2.10.3 | Gestionnaire des dépendances PHP |
| Laravel | 13.32.0 | Version verrouillée dans `composer.lock` |
| MySQL | 8.4.7 | Serveur de données local prévu sur le port 3306 |
| Node.js | 26.1.0 présent | Node.js 24 LTS est recommandé pour un environnement durable |
| npm | 11.13.0 | Installation et compilation des ressources front-end |
| Interface | Blade, Vite, Bootstrap, Chart.js | Rendu serveur, styles responsifs et graphiques |

Les résultats effectivement observés et les contrôles qui restent à réaliser sont consignés dans [`docs/VALIDATION.md`](docs/VALIDATION.md).

## Installation locale sous Windows avec WampServer

### 1. Prérequis

Installer ou activer :

- WampServer avec Apache, PHP 8.4.15 et MySQL 8.4.7 ;
- Composer 2.10.3 ou une version compatible plus récente ;
- Node.js 24 LTS recommandé, ou la version 26 déjà présente sur le poste ;
- Git.

Dans WampServer, sélectionner PHP 8.4.15 puis démarrer Apache et MySQL. MariaDB n'est pas nécessaire pour ABIWEIN. Vérifier dans PowerShell :

```powershell
php --version
composer --version
mysql --version
node --version
npm --version
```

### 2. Récupération et dépendances

```powershell
git clone <URL_PRIVEE_DU_DEPOT> ABIWEIN
Set-Location ABIWEIN
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Si `package-lock.json` est présent, utiliser l'installation reproductible :

```powershell
npm ci
```

Sinon :

```powershell
npm install
```

### 3. Base de données

Créer une base et un compte dédiés ; l'application ne doit pas utiliser le compte administrateur MySQL au quotidien.

```sql
CREATE DATABASE abiwein
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'abiwein_app'@'127.0.0.1'
    IDENTIFIED BY '<MOT_DE_PASSE_LOCAL_FORT>';

GRANT ALL PRIVILEGES ON abiwein.* TO 'abiwein_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Configurer ensuite le fichier `.env` local :

```dotenv
APP_NAME=ABIWEIN
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR
APP_TIMEZONE=Africa/Douala

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=abiwein
DB_USERNAME=abiwein_app
DB_PASSWORD=<MOT_DE_PASSE_LOCAL_FORT>
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_ENGINE=InnoDB
```

Le fichier `.env` contient des secrets : il reste local, ne doit jamais être joint à un message et ne doit pas être ajouté à Git.

Initialiser le schéma :

```powershell
php artisan config:clear
php artisan migrate
```

### 4. Démarrage

Pour lancer l'ensemble des processus de développement :

```powershell
composer run dev
```

Pour lancer séparément le serveur Laravel et Vite :

```powershell
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

Ouvrir ensuite `http://127.0.0.1:8000`. Apache de WampServer pourra aussi servir l'application avec un hôte virtuel dont la racine pointe vers le dossier `public`; cette configuration est facultative pour le développement initial.

## Compilation et contrôles

```powershell
npm run build
composer validate --strict
composer check-platform-reqs
composer audit
php artisan test
php artisan migrate:status
```

Ces contrôles ont réussi sur le poste de développement le 21 septembre 2026. Les preuves synthétiques et les éventuels contrôles restant à faire sont consignés dans [`docs/VALIDATION.md`](docs/VALIDATION.md).

## Organisation du projet

```text
app/                 logique applicative Laravel
config/              configuration de l'application
database/            migrations, fabriques et données de démonstration
public/              point d'entrée web et ressources publiques
resources/views/     écrans Blade
resources/scss/      styles et variables de marque
resources/js/        JavaScript et initialisation des graphiques
routes/               routes web et autres points d'entrée
tests/               tests automatisés
docs/                architecture, marque et journal de validation
```

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) : architecture et frontières du socle.
- [`docs/BRAND.md`](docs/BRAND.md) : palette, typographie et règles du logo.
- [`docs/CADRAGE.md`](docs/CADRAGE.md) : périmètre, acteurs et règles fonctionnelles validées.
- [`docs/FRONTEND_CUSTOMIZATION.md`](docs/FRONTEND_CUSTOMIZATION.md) : repères pour modifier chaque partie de l'interface.
- [`docs/IMAGE_CREDITS.md`](docs/IMAGE_CREDITS.md) : sources et licences des photographies professionnelles.
- [`docs/VALIDATION.md`](docs/VALIDATION.md) : preuves observées et contrôles restants.

## Gestion du code

La branche principale est `main`. Le dépôt GitHub doit rester privé. Ne jamais versionner `.env`, les mots de passe, les sauvegardes de base de données ni les fichiers contenant des données réelles de l'exploitation.
