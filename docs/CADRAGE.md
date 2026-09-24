# Cadrage fonctionnel d’ABIWEIN

## 1. Finalité

ABIWEIN est une application web responsive de gestion d’une exploitation de poulets de chair. Elle centralise le suivi technique, sanitaire, commercial et financier, puis fournit des indicateurs et une assistance à l’analyse. L’intelligence artificielle reste un outil d’aide : une personne autorisée valide toujours les décisions importantes.

## 2. Périmètre public

Le visiteur non connecté peut :

- consulter l’accueil et la présentation de l’exploitation ;
- comprendre les grandes activités de la ferme sans voir de données internes ;
- envoyer une demande par le formulaire de contact ;
- accéder à la page d’entrée de l’espace privé.

Il ne peut ni créer un compte, ni consulter les bandes, stocks, ventes, dossiers sanitaires, dépenses, tableaux de bord ou alertes. Aucune inscription publique n’est prévue.

## 3. Périmètre privé prévu

Trois rôles accéderont à l’application après l’étape d’authentification :

- **Responsable d’exploitation** : pilotage courant des bandes, stocks, opérations commerciales et financières ;
- **Vétérinaire** : accès aux dossiers et interventions sanitaires selon les autorisations définies ;
- **Administrateur** : gestion des comptes, paramètres, droits et contrôles techniques.

Les comptes sont créés ou bloqués par un administrateur. Les droits détaillés seront formalisés avant le développement de chaque module.

## 4. Règles métier validées

- Une vente est réalisée **au comptant** ou **à crédit**.
- Le seul moyen de règlement accepté est **l’espèce**.
- Une vente à crédit peut recevoir plusieurs versements en espèces jusqu’au règlement complet.
- Les alertes sont intégrées à ABIWEIN ; aucun SMS, e-mail ou service externe n’est requis au départ.
- Les informations internes ne sont jamais exposées dans l’espace visiteur.
- Les analyses produites par l’IA doivent rester explicables et soumises à une validation humaine.

## 5. Activités couvertes à terme

- fournisseurs, bâtiments, bandes, affectations, transferts et mouvements d’effectif ;
- suivi quotidien : mortalité, alimentation, eau, poids, état sanitaire et observations ;
- produits, entrées, sorties, inventaires, seuils et expirations ;
- dossiers sanitaires, visites vétérinaires, diagnostics, programmes et interventions ;
- clients, ventes, garanties, versements, créances, dépenses et bilans ;
- tableaux de bord, rapports, alertes et analyses assistées.

## 6. Principes de réalisation

- Une vue Blade dédiée par écran, avec des partiels pour l’en-tête et le pied de page.
- Des commentaires de maintenance au-dessus de chaque bloc logique et valeur importante à personnaliser.
- Une palette sobre à blanc dominant : bleu foncé pour les actions et le texte, blanc pour les pages et bleu clair pour les surfaces secondaires.
- Aucune image générée et aucun emoji dans l’interface.
- Des photographies professionnelles dont la source et la licence sont conservées dans `docs/IMAGE_CREDITS.md`.
- Une expérience utilisable au clavier et sur téléphone, tablette et ordinateur.

## 7. Limite du présent lot

Ce lot réalise le cadrage, l’identité visuelle et l’espace public. La base métier définitive, l’authentification opérationnelle, les rôles et les modules de gestion seront réalisés ensuite. La page de connexion actuelle est donc un écran d’entrée désactivé, sans faux identifiant ni fausse session.
