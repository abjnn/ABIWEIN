# Validation technique — étape 2

Dernière mise à jour documentaire : **21 septembre 2026**.

Ce document sépare les faits déjà observés sur le poste de développement des contrôles d'intégration encore à exécuter. Un logiciel détecté ou un fichier présent ne prouve pas, à lui seul, que l'ensemble de l'application fonctionne.

## 1. Résultats déjà observés

| Contrôle | Résultat observé | Portée de la preuve |
|---|---|---|
| Système | Windows 10 x64, build 19045.6466 | Poste de développement identifié |
| Espace disque | Environ 165,6 Go libres au moment du contrôle | Suffisant pour le socle local |
| WampServer | Version 3.4.0 installée dans `C:\wamp64` | Apache et MySQL démarrés ; MariaDB laissé à l'arrêt |
| PHP CLI | PHP 8.4.15 exécutable | Version utilisable en ligne de commande |
| Extensions PHP | `pdo_mysql`, `curl`, `mbstring`, `intl`, `zip` et `gd` détectées | Prérequis Laravel principaux présents pour PHP 8.4.15 |
| PHP 8.5 | Installation détectée avec une DLL Xdebug incompatible, destinée à PHP 8.4 | Version écartée ; PHP 8.4.15 est la référence du projet |
| Composer | Version 2.10.3 installée | Mise à niveau effectuée depuis une version ancienne concernée par des avis de sécurité |
| Composer PHAR | Empreinte SHA-256 comparée à l'empreinte officielle de Composer 2.10.3 | Intégrité du fichier téléchargé contrôlée |
| Connectivité Composer | Accès HTTP/HTTPS à Packagist réussi pendant le diagnostic | Connectivité observée à cet instant |
| Laravel | `laravel/framework` v13.32.0 présent dans `composer.lock` | Version résolue, verrouillée et testée |
| MySQL | MySQL 8.4.7 actif sur `127.0.0.1:3306` avec `utf8mb4` | Connexion TCP et compte applicatif validés |
| Configuration MySQL | `mysqld --validate-config` terminé avec un code de succès | Syntaxe de configuration acceptée ; connexion applicative non encore prouvée |
| MariaDB | MariaDB 11.4.9 détectée sur le port 3307 | Non requise pour ABIWEIN et laissée à l'arrêt lors du contrôle |
| Apache | Apache 2.4.65 actif ; `httpd -t` a retourné `Syntax OK` | Service démarré après bascule Wamp vers PHP 8.4.15 |
| Front-end | Bootstrap 5.3.8, Popper 2.11.8, Chart.js 4.5.1, Sass 1.104.1 et Vite 7.3.6 | Dépendances verrouillées par `package-lock.json` et build réussi |
| Audits | `composer audit` et `npm audit --omit=dev` | Aucun avis de vulnérabilité détecté |
| Base applicative | Base `abiwein`, compte `abiwein_app@127.0.0.1`, tables InnoDB | Compte dédié et migrations Laravel validés |
| Tests | `php artisan test` | 3 tests réussis, 4 assertions |
| HTTP | `/`, `/up`, JavaScript et CSS compilés | Toutes les requêtes ont répondu HTTP 200 |
| Node.js | v26.1.0 | Version présente et exécutable ; Node.js 24 LTS reste recommandé pour la stabilité à long terme |
| npm | v11.13.0 | Gestionnaire front-end présent |
| Git | v2.55.0.windows.2 | Client Git présent |
| Visual Studio Code | v1.135.0 | Éditeur présent |
| Extension Codex | `openai.chatgpt` v26.908.40401 installée et activée | Extension disponible dans Visual Studio Code |
| Session Codex | État `Logged in using ChatGPT` observé | Authentification Codex confirmée au moment du contrôle |
| Dépôt local | Répertoire `.git` présent | Initialisation locale détectée ; publication distante traitée séparément |

## 2. Résultats de la passe d'acceptation

| Contrôle | Résultat du 21 septembre 2026 |
|---|---|
| PHP de WampServer | PHP 8.4.15 sélectionné ; configuration Apache valide |
| Services | `wampapache64` et `wampmysqld64` actifs ; `wampmariadb64` arrêté |
| Configuration Laravel | Nom ABIWEIN, locale `fr`, fuseau `Africa/Douala`, connexion `mysql` |
| Composer | Définition valide, prérequis PHP satisfaits, aucun avis de sécurité |
| MySQL | Base et utilisateur dédiés opérationnels, trois migrations appliquées avec InnoDB |
| Front-end | 83 paquets installés, build Vite produit, aucun avis npm de niveau production |
| Tests automatisés | 3 tests réussis, 4 assertions |
| HTTP | Accueil, santé, CSS et JavaScript : HTTP 200 |
| Marque | Logo officiel et cinq couleurs exactes intégrés |
| Secrets Git | `.env` ignoré ; aucun secret ajouté à l'index |

La publication sur le dépôt GitHub privé est validée séparément après le premier `git push`. Les avertissements Sass observés pendant le build proviennent des imports historiques de Bootstrap 5.3 ; ils sont non bloquants et le build se termine avec succès.

## 3. Séquence de validation recommandée

```powershell
php --version
composer --version
composer validate --strict
composer check-platform-reqs
composer audit

php artisan config:clear
php artisan about
php artisan migrate
php artisan migrate:status

npm ci
npm run build

php artisan test
git check-ignore .env
```

Si `package-lock.json` n'existe pas encore, remplacer `npm ci` par `npm install`, puis conserver le fichier de verrouillage généré.

## 4. Preuves à conserver

Pour chaque contrôle complété, consigner :

- la date et l'environnement ;
- la commande exacte ;
- le code de sortie ;
- le résumé du résultat, sans secret ;
- l'action corrective en cas d'échec.

Ne jamais copier dans ce document le contenu de `.env`, un mot de passe MySQL, une clé d'application, un jeton GitHub ou des données réelles de l'exploitation.
