# Cadrage fonctionnel d’ABIWEIN

## 1. Finalité

ABIWEIN est une application web responsive de gestion d’une exploitation de poulets de chair. Elle centralise le suivi technique, sanitaire, commercial et financier, puis fournit des indicateurs utiles au pilotage de la ferme.

## 2. Périmètre public

Le visiteur non connecté peut :

- consulter l’accueil et la présentation de l’exploitation ;
- comprendre les grandes activités de la ferme sans voir de données internes ;
- envoyer une demande par le formulaire de contact ;
- accéder à la page d’entrée de l’espace privé.

Il ne peut ni créer un compte, ni consulter les bandes, stocks, ventes, dossiers sanitaires, dépenses, tableaux de bord ou alertes. Aucune inscription publique n’est prévue.

## 3. Périmètre privé actuel

L'application possède exactement quatre acteurs : le visiteur et trois rôles authentifiés. Les rôles privés sont :

- **Responsable d’exploitation** : pilotage courant des bandes, stocks, opérations commerciales et financières ;
- **Vétérinaire** : consultation technique des bandes et accès aux dossiers et interventions sanitaires selon les autorisations définies ;
- **Administrateur** : gestion des comptes, paramètres, droits et contrôles techniques.

Les comptes sont créés ou bloqués par un administrateur. Les droits détaillés sont appliqués dans les routes, validations et contrôleurs ; l’administrateur supervise les opérations en lecture seule, le responsable les exécute et le vétérinaire consulte les informations techniques des bandes nécessaires au suivi sanitaire sans pouvoir les modifier.

## 4. Règles métier validées

- Une vente est réalisée **au comptant** ou **à crédit**.
- Le seul moyen de règlement accepté est **l’espèce**.
- Une vente à crédit peut recevoir plusieurs versements en espèces jusqu’au règlement complet.
- Les alertes sont intégrées à ABIWEIN ; aucun SMS, e-mail ou service externe n’est requis au départ.
- Les informations internes ne sont jamais exposées dans l’espace visiteur.

## 5. Activités couvertes

- fournisseurs, bâtiments, bandes, affectations, transferts et mouvements d’effectif ;
- suivi quotidien : mortalité, alimentation, eau, poids, état sanitaire et observations ;
- produits, entrées, sorties, inventaires, seuils et expirations ;
- dossiers sanitaires, visites vétérinaires, diagnostics, programmes et interventions ;
- clients, ventes, garanties, versements, créances, dépenses et bilans ;
- tableaux de bord, rapports et alertes internes.

## 6. Principes de réalisation

- Une vue Blade dédiée par écran, avec des partiels pour l’en-tête et le pied de page.
- Des commentaires de maintenance au-dessus de chaque bloc logique et valeur importante à personnaliser.
- Une palette sobre à blanc dominant : vert forêt pour les actions et le texte, vert sauge pour les éléments secondaires, ivoire pour les surfaces douces et or discret pour les accents et graphiques.
- Aucune image générée et aucun emoji dans l’interface.
- Des photographies professionnelles dont la source et la licence sont conservées dans `docs/IMAGE_CREDITS.md`.
- Une expérience utilisable au clavier et sur téléphone, tablette et ordinateur.

## 7. État actuel et limites

L’identité, l’espace public, l’authentification, les trois rôles privés, les bandes, les relevés quotidiens, les stocks, les visites vétérinaires, le commerce, les finances, les rapports PDF et l’audit sont présents dans le code. Le module Bandes permet au responsable de créer, modifier, transférer, clôturer et archiver une bande, puis de consigner les mesures et actions horodatées de la journée. L’administrateur et le vétérinaire consultent ce registre sans l’écrire. Les écrans autonomes de gestion des fournisseurs et bâtiments restent à compléter. Les preuves techniques du lot du 27 septembre sont consignées dans `docs/VALIDATION.md`.
