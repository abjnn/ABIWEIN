# Architecture d'ABIWEIN

## 1. Objectif du socle

ABIWEIN est conçu comme une application web monolithique Laravel. Ce choix garde le développement, le déploiement et la maintenance simples : les règles métier, les écrans et l'accès aux données vivent dans un même projet, avec des responsabilités clairement séparées.

Le socle de l'étape 2 prépare l'infrastructure. Les modules métier détaillés, leurs migrations et leurs règles d'autorisation seront implémentés lors des étapes suivantes.

## 2. Vue d'ensemble

```text
Navigateur responsive
        |
        | HTTP
        v
Routes Laravel -> contrôleurs -> services/règles métier -> modèles Eloquent
        |                                                    |
        v                                                    v
Vues Blade + Vite                                  MySQL 8.4
Bootstrap + Chart.js
```

- **Laravel 13.32.0** porte le routage, la validation, l'authentification, les autorisations, la logique métier et la persistance.
- **Blade** génère les pages côté serveur.
- **Vite** compile et versionne les ressources front-end.
- **Bootstrap** fournit la grille et les composants responsifs.
- **Chart.js** affiche les indicateurs et tendances du tableau de bord.
- **MySQL 8.4.7** conserve les données de l'exploitation.

## 3. Couches applicatives

### Présentation

Les routes web dirigent les requêtes vers des contrôleurs minces. Les contrôleurs valident l'entrée, délèguent les opérations métier et retournent des vues Blade ou une redirection. Les composants visuels utilisent les jetons définis dans `resources/scss` et le logo publié dans `public/brand`.

L'interface est rédigée en français, utilise le fuseau `Africa/Douala` et doit rester utilisable au clavier ainsi que sur les petits écrans.

### Domaine

La logique métier doit être isolée des vues et, lorsqu'elle dépasse une opération simple, extraite dans des classes d'action ou de service sous `app/`. Les principaux domaines prévus sont :

- utilisateurs et droits d'accès ;
- bandes, sujets et mouvements d'effectif ;
- alimentation et stocks ;
- suivi sanitaire ;
- charges et autres opérations financières ;
- ventes, créances et règlements ;
- alertes internes ;
- tableau de bord et indicateurs.

Cette liste décrit les frontières prévues, pas des modules déjà achevés.

### Persistance

Les modèles Eloquent représentent les entités. Toute modification du schéma passe par une migration versionnée dans `database/migrations`. Les montants monétaires doivent être stockés avec un type décimal, jamais avec un nombre flottant. Les dates métier sont enregistrées de manière cohérente et affichées dans le fuseau `Africa/Douala`.

Les contraintes importantes doivent exister à deux niveaux : validation Laravel pour produire un message clair, puis contrainte MySQL lorsque l'intégrité des données l'exige.

## 4. Règles structurantes

### Ventes et paiements

Une vente possède un mode de vente :

- `comptant` : le montant attendu est réglé immédiatement ;
- `credit` : tout ou partie du montant reste dû.

Le moyen de paiement autorisé est **l'espèce uniquement**. Le mode de vente et le moyen de paiement sont deux concepts distincts. Une vente à crédit peut recevoir plusieurs versements, chacun en espèces, jusqu'à extinction du solde. Les enregistrements financiers doivent conserver le montant, la date, l'auteur et la vente concernée afin de permettre le contrôle et l'historique.

### Alertes

Les alertes sont créées, consultées et acquittées dans ABIWEIN. Elles peuvent être issues d'un seuil, d'une échéance ou d'une anomalie métier. Aucun canal externe — e-mail, SMS ou notification poussée — ne fait partie du socle initial.

### Responsive

Les écrans sont pensés en priorité pour les tâches essentielles : navigation courte, formulaires utilisables sur téléphone, tableaux qui se replient proprement et actions tactiles suffisamment grandes. Bootstrap apporte la grille ; les règles de marque complètent ses variables sans dupliquer son système de composants.

## 5. Configuration et sécurité

- Le fichier `.env` fournit les secrets et les réglages propres au poste ; il n'est jamais versionné.
- `.env.example` décrit uniquement les clés attendues et ne contient aucun secret réel.
- MySQL utilise une base `abiwein` et un compte applicatif dédié, limité à cette base.
- `APP_DEBUG` doit être `false` hors développement local.
- Les entrées sont validées côté serveur, même si une aide existe dans le navigateur.
- Les mutations utilisent la protection CSRF de Laravel.
- Les autorisations seront portées par les policies/gates Laravel au niveau des actions métier.
- Les journaux ne doivent contenir ni mot de passe ni donnée personnelle inutile.
- Le dépôt GitHub reste privé et les dépendances sont verrouillées par `composer.lock` et `package-lock.json` lorsqu'il existe.

## 6. Environnements

### Développement local

WampServer fournit Apache, PHP 8.4.15 et MySQL 8.4.7. Laravel peut être servi directement par `php artisan serve` pendant le développement. Vite s'exécute avec `npm run dev`.

### Compilation

`npm run build` génère les ressources optimisées dans `public/build`. Le contenu généré ne remplace pas les sources de `resources/`.

### Tests

Les tests PHPUnit/Laravel couvrent au minimum le démarrage de l'application, la page d'accueil et la route de santé. Les futures règles de ventes, règlements, stocks et alertes devront disposer de tests de comportement et de tests d'autorisation.

## 7. Principes d'évolution

- Préférer les conventions Laravel aux abstractions spécifiques sans besoin démontré.
- Garder les contrôleurs courts et les règles métier testables indépendamment.
- Ajouter le schéma par migrations réversibles.
- Éviter les dépendances externes pour les fonctions déjà couvertes par le framework.
- Documenter toute décision qui modifie les frontières, la sécurité ou le modèle de données.
