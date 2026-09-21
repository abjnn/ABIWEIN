# Base de données, démonstration et accès

Ce document décrit le socle actuellement exécutable. Les données créées par les seeders sont fictives et réservées au développement local.

## Modules de données disponibles

| Domaine | Tables principales | Usage |
|---|---|---|
| Accès | `users` | Comptes, rôles, état actif ou bloqué, dernière connexion |
| Exploitation | `suppliers`, `buildings`, `flocks`, `flock_building_assignments`, `flock_movements` | Fournisseurs, bâtiments, bandes, affectations et mouvements d’effectifs |
| Suivi quotidien | `daily_records` | Mortalité, aliment, eau, poids moyen, état sanitaire et observations |
| Stocks | `products`, `stock_movements` | Intrants de la ferme, quantités, seuils d’alerte, entrées et sorties |
| Commerce | `clients`, `sales`, `sale_items`, `payments`, `expenses` | Ventes comptant ou crédit, paiements en espèces et dépenses |
| Santé | `veterinary_visits` | Visites, diagnostics, prescriptions et suivi vétérinaire |
| Alertes | `alerts` | Alertes internes à l’application et état de lecture |
| Rapports | `farm_reports` | Documents administratifs archivés et snapshots figés pour le PDF |
| Assistant | `ai_messages` | Historique privé des conversations du responsable et de l’administrateur |

## Rôles

| Rôle | Portée actuelle |
|---|---|
| Administrateur | Supervision en lecture seule des opérations, rapports/PDF, assistant ABIWEIN, comptes et journal d’audit |
| Responsable d’exploitation | Saisie des stocks, clients, ventes, paiements et dépenses, rapports, assistant ABIWEIN et application des recommandations |
| Vétérinaire | Tableau de bord technique et création/consultation des visites sanitaires |

## Comptes locaux de démonstration

Après `php artisan db:seed`, trois comptes fictifs sont créés :

| Rôle | Adresse | Mot de passe local |
|---|---|---|
| Administrateur — Nadège Nkomo | `nadege.nkomo@abiwein.local` | `Nadege#Abiwein26!` |
| Responsable — Alain Tchoumi | `alain.tchoumi@abiwein.local` | `Ferme#Tchoumi26!` |
| Vétérinaire — Dre Mireille Ngono | `mireille.ngono@abiwein.local` | `Sante#Ngono2026!` |

Ces identifiants sont volontairement documentés parce qu’ils ne doivent servir qu’à la démonstration locale. Les adresses en `.local` sont des identifiants internes et non des boîtes e-mail publiques. Le seeder refuse de les créer lorsque `APP_ENV=production`.

## Initialisation locale

```powershell
php artisan migrate
php artisan db:seed
```

Pour reconstruire intégralement une base locale de démonstration :

```powershell
php artisan migrate:fresh --seed
```

La seconde commande efface les tables avant de les recréer. Elle ne doit jamais être lancée sur une base contenant des données utiles.
