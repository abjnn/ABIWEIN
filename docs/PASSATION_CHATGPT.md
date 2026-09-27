# Passation complète d’ABIWEIN vers ChatGPT

> **État documentaire : 27 septembre 2026.**  
> Ce document décrit l’état du dépôt après l’ajout du module Bandes et la simplification fonctionnelle du projet. Les validations techniques du lot sont consignées dans `docs/VALIDATION.md`. Ne jamais reproduire le contenu de `.env`, un mot de passe de production, un jeton GitHub ou les identifiants de la base.

## 1. Identité et objectif

- Nom exact : **ABIWEIN**.
- Application web française et responsive de gestion d’une exploitation de poulets de chair.
- Objectif : centraliser bandes, suivi quotidien, intrants, santé, ventes, paiements, dépenses, rapports, alertes et indicateurs de pilotage.
- Sources initiales : `Rapport de stage.docx`, `Rapport de stage.pdf` et `ABIWEIN_Dossier_passation_Codex.pdf`.
- Inspiration visuelle : espace public de `https://app.comagropht.cm/`, adapté sans copie littérale.

## 2. Emplacement et Git

| Élément | Valeur |
|---|---|
| Dossier local | `C:\Users\AMBIANA\Documents\ChatGPT\Application ABIWEIN` |
| Dépôt privé | `https://github.com/abjnn/ABIWEIN.git` |
| Branche | `main` |
| Dernier commit avant le lot en cours | `09a864d8a9020ccfeaa0a29002817be16d31593d` |
| URL locale | `http://127.0.0.1:8000` |
| URL publique validée | Aucune |

Le lot du 27 septembre comporte des modifications non commitées. Vérifier `git status` et les tests avant tout commit ou déploiement.

## 3. Acteurs

ABIWEIN possède exactement quatre acteurs :

1. **Visiteur** : consulte les pages publiques, contacte la ferme et accède à la connexion.
2. **Administrateur** : gère les comptes et l’audit, puis supervise les données métier en lecture seule.
3. **Responsable d’exploitation** : exécute les opérations de la ferme, notamment la gestion des bandes et le suivi quotidien.
4. **Vétérinaire** : consulte les données techniques des bandes, puis rédige ses visites, diagnostics et recommandations sanitaires.

Les restrictions sont imposées par les routes, middlewares, validations et contrôleurs, pas seulement par la navigation.

## 4. Décisions métier

1. Le paiement est effectué uniquement en espèces.
2. Une vente est au comptant ou à crédit ; plusieurs versements peuvent solder une créance sans dépasser le reste dû.
3. Les stocks représentent les intrants consommés par la ferme et ne sont pas vendus.
4. Les alertes restent internes à ABIWEIN.
5. Il n’existe aucune inscription publique ; seul l’administrateur crée ou bloque les comptes.
6. L’administrateur supervise mais ne saisit pas les opérations de ferme.
7. Le responsable est le seul rôle autorisé à écrire dans le module Bandes.
8. Le vétérinaire consulte les données techniques des bandes en lecture seule ; il n’accède pas aux stocks, au commerce ou aux finances.
9. Les rapports sont des documents administratifs archivés et téléchargeables en PDF.
10. Les données historiques sont préservées par la désactivation, la clôture ou l’archivage plutôt que par la suppression destructive.

## 5. Fonctionnalités présentes

### Espace public

- Accueil, à-propos, activités, contact et connexion dans des vues Blade séparées.
- Formulaire de contact avec nom, prénom, domaine de courriel vérifié, téléphone camerounais normalisé en `+237…`, sujet, message, consentement et champ anti-robot.
- Envoi par le système de courrier Laravel ; un SMTP doit être configuré pour un envoi réel.
- Photographies professionnelles dont les crédits figurent dans `docs/IMAGE_CREDITS.md`.

### Authentification et comptes

- Connexion, déconnexion, session régénérée, mémorisation facultative et limitation des tentatives.
- Comptes actifs ou bloqués, adresse unique et mots de passe robustes distincts.
- Création et blocage des comptes réservés à l’administrateur.
- Absents : récupération de mot de passe et double authentification.

### Bandes et suivi quotidien

- Registre filtrable par référence, souche, statut et bâtiment.
- Fiche détaillée regroupant identité, bâtiment, effectif, relevés, chronologie, mouvements et dernières visites.
- Création avec arrivée initiale et première affectation éventuelle.
- Modification des informations descriptives sans contourner l’historique d’effectif.
- Transfert complet vers un bâtiment compatible après contrôle de capacité.
- Clôture d’une bande active puis archivage d’une bande clôturée.
- Relevé journalier unique : mortalité, aliment, eau, poids moyen, état sanitaire et observations.
- Chronologie horodatée : inspection, alimentation, abreuvement, pesée, mortalité, contrôle de l’ambiance, nettoyage ou désinfection, soin appliqué et autre intervention.
- Synchronisation transactionnelle des quantités mesurées, de la mortalité et de l’effectif courant.
- Administrateur et vétérinaire : consultation uniquement. Responsable : consultation et écriture.

### Tableau de bord

- Vue adaptée au rôle avec indicateurs compacts, graphiques Chart.js, alertes, bandes actives et derniers relevés.
- Mise en page inspirée de la capture fournie : blanc dominant, texte sombre, bordures légères, graphique principal, panneau de surveillance et tableaux denses.
- Accès au registre des bandes et à la saisie d’un relevé pour le responsable.

### Stocks

- Intrants, catégories, unités, seuils, dates d’expiration et état actif.
- Entrées, sorties et ajustements avec historique et transactions.
- Refus d’une sortie supérieure à la quantité disponible.
- Alertes de seuil et d’expiration.

### Santé

- Liste, consultation et création de visites par le vétérinaire connecté.
- Diagnostic, recommandations et prochaine visite.
- Le responsable confirme l’application d’une recommandation ; l’administrateur consulte seulement.

### Commerce et finances

- Clients, ventes à lignes multiples, créances, versements en espèces et dépenses.
- Recalcul serveur des montants, références automatiques et verrouillage transactionnel.
- Statuts payée, partielle ou impayée calculés côté serveur.

### Rapports

- Types journalier, sanitaire, stocks, commercial/financier et synthèse.
- Création par le responsable d’un instantané archivé.
- Consultation par le responsable et l’administrateur.
- Téléchargement d’un document PDF A4 généré par Dompdf.

### Audit et sécurité

- Journalisation des principales écritures sensibles, y compris les bandes et leur suivi.
- Exclusion des mots de passe et jetons des journaux.
- CSRF, hachage des mots de passe, validations serveur, requêtes paramétrées, transactions et en-têtes de sécurité.

## 6. Données principales

Tables métier principales :

- `users` ;
- `suppliers`, `buildings`, `flocks`, `flock_building_assignments`, `flock_movements` ;
- `daily_records`, `flock_activities` ;
- `products`, `stock_movements` ;
- `clients`, `sales`, `sale_items`, `payments`, `expenses` ;
- `veterinary_visits`, `alerts`, `audit_logs`, `farm_reports`.

MySQL est utilisé localement, PostgreSQL Neon est prévu pour la production et SQLite en mémoire sert aux tests.

## 7. Comptes locaux fictifs

| Rôle | Identifiant | Mot de passe local |
|---|---|---|
| Administratrice — Nadège Nkomo | `nadege.nkomo@abiwein.local` | `Nadege#Abiwein26!` |
| Responsable — Alain Tchoumi | `alain.tchoumi@abiwein.local` | `Ferme#Tchoumi26!` |
| Vétérinaire — Dre Mireille Ngono | `mireille.ngono@abiwein.local` | `Sante#Ngono2026!` |

Ces accès servent uniquement à la démonstration locale et doivent être remplacés avant toute utilisation réelle.

## 8. Design

- Blanc dominant, texte presque noir et bordures gris clair pour le tableau de bord.
- Vert forêt `#234B35` pour les actions principales.
- Vert sauge `#6F835D` pour les éléments secondaires.
- Ivoire `#F2EBDD` pour les fonds doux.
- Or `#C99A2E` pour quelques accents et séries de graphiques.
- Typographie Roboto et logo officiel `public/brand/abiwein-logo.png`.
- Listes et tableaux compacts plutôt que grands rectangles décoratifs.
- Vues Blade séparées et commentaires de maintenance autour des blocs importants.

## 9. Architecture

ABIWEIN est un monolithe Laravel/Blade modulaire : routes → middlewares → Form Requests → contrôleurs/services → modèles Eloquent → base relationnelle → vues Blade.

Le module Bandes place les règles transactionnelles dans `app/Services/Flock/FlockOperationService.php`. Ses routes se trouvent dans `routes/flocks.php`, ses contrôleurs dans `app/Http/Controllers/Flock`, ses validations dans `app/Http/Requests/Flock` et ses vues dans `resources/views/flocks`.

Pile principale : PHP 8.4, Laravel 13, MySQL, Blade, Bootstrap 5.3, Chart.js 4.5, Sass, Vite, Dompdf, PHPUnit, Git et GitHub privé.

## 10. Validation

- Suite complète du 27 septembre 2026 : **75 tests et 400 assertions réussis**.
- Les **12 migrations** locales sont appliquées ; le module Bandes et le retrait de l'assistance artificielle sont couverts.
- Le build Vite (121 modules) et le cache des vues Blade ont réussi. Le contrôle visuel final sur les tailles d'écran de soutenance reste manuel.

Commandes attendues :

```powershell
php artisan migrate:status
php artisan test
npm run build
git diff --check
```

## 11. Limites connues

- Les fournisseurs et bâtiments existent dans le modèle et servent aux bandes, mais leurs écrans autonomes restent à compléter.
- Aucun écran dédié d’acquittement des alertes ni réévaluation planifiée des expirations.
- Pas d’annulation de vente, reçu/facture PDF, garantie, échéance de créance, pièce jointe sanitaire ou liaison entre vente et bande.
- Pas de récupération de mot de passe ni de double authentification.
- L’envoi réel du contact nécessite un SMTP.
- Le déploiement Render/Neon n’est pas terminé et aucune URL publique n’est validée.

## 12. Priorités suivantes

1. Effectuer le contrôle visuel final sur ordinateur et téléphone.
2. Commiter et pousser le lot validé.
3. Compléter les écrans fournisseurs et bâtiments si le périmètre les exige.
4. Renforcer récupération de compte, sauvegardes, alertes planifiées et contrôles de production.
5. Terminer Render/Neon et valider une véritable URL publique.
6. Préparer les captures et documents de soutenance.

## 13. Fichiers de référence

- `README.md` — installation et commandes.
- `docs/ARCHITECTURE.md` — choix techniques et architecture.
- `docs/CADRAGE.md` — périmètre et règles.
- `docs/DATABASE_AND_ACCESS.md` — tables, acteurs et comptes fictifs.
- `docs/BRAND.md` — identité et règles visuelles.
- `docs/VALIDATION.md` — preuves observées et contrôles restant à exécuter.
- `routes/*.php`, `app/Services`, `app/Http/Requests`, `app/Models`, `resources/views`, `database/migrations` et `tests` — vérité exécutable.

## 14. Instruction de reprise

> Lis cette passation comme l’état de référence d’ABIWEIN au 27 septembre 2026. Travaille en français. Vérifie le dépôt avant d’affirmer qu’une fonctionnalité existe. Préserve les quatre acteurs, leurs droits, les règles métier et la palette. Ne divulgue aucun secret. Distingue toujours ce qui est terminé, ce qui reste à valider et ce qui est absent.
