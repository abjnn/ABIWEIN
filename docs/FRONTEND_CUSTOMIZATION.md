# Guide de modification de l’interface ABIWEIN

Ce guide indique précisément où intervenir. Les commentaires `MODIFICATION` dans le code donnent ensuite le détail local.

## Pages et fichiers

| Besoin | Fichier à modifier |
|---|---|
| Contenu de l’accueil | `resources/views/pages/home.blade.php` |
| Contenu de la page À propos | `resources/views/pages/about.blade.php` |
| Contenu et champs du contact | `resources/views/pages/contact.blade.php` |
| Aperçu de la connexion | `resources/views/auth/login.blade.php` |
| Tableau de bord privé | `resources/views/dashboard/index.blade.php` |
| Gestion des utilisateurs | `resources/views/admin/users/` |
| Liens du menu | `resources/views/partials/public/header.blade.php` |
| Menu privé | `resources/views/partials/private/sidebar.blade.php` |
| Contenu du pied de page | `resources/views/partials/public/footer.blade.php` |
| Structure HTML commune | `resources/views/layouts/public.blade.php` |
| Couleurs et typographie | `resources/scss/_brand.scss` |
| Dimensions, espacements et mise en page | `resources/scss/_public.scss` |
| Mise en page de l’espace privé | `resources/scss/_private.scss` |
| Adresses des pages | `routes/web.php` |
| Coordonnées publiques | `.env`, à partir des clés de `.env.example` |

## Remplacer une photo

Les photos sont dans `public/images/public`. Conserver le même nom permet de les remplacer sans toucher au code :

- `hero-poultry.jpg` pour le grand visuel d’accueil et À propos ;
- `farmer-poultry.jpg` pour les jeunes poulets autour d’une mangeoire ;
- `prepared-chicken.jpg` pour les poulets entiers conditionnés ;
- `private-access-poultry.jpg` pour la connexion, conservé indépendamment des autres pages.
- `activity-growth.jpg` pour la carte Élevage et croissance ;
- `activity-health.jpg` pour la carte Santé et prévention.

Vérifier la licence, privilégier une image horizontale d’au moins 1 400 pixels, l’optimiser pour le web et mettre à jour `docs/IMAGE_CREDITS.md`.

## Couleurs, tailles et espacements

Changer une variable dans `resources/scss/_brand.scss` modifie tous les composants qui l’utilisent. Les cinq codes officiels figurent dans `docs/BRAND.md`. Dans `_public.scss`, chaque groupe de règles porte le nom de la zone concernée et les valeurs principales sont commentées.

Après toute modification Sass ou JavaScript :

```powershell
npm run build
```

## Coordonnées publiques

Renseigner les clés suivantes dans `.env` :

```dotenv
PUBLIC_PHONE=
PUBLIC_CONTACT_EMAIL=
PUBLIC_ADDRESS=
PUBLIC_BUSINESS_HOURS=
PUBLIC_WHATSAPP=
```

Une valeur vide masque automatiquement la ligne correspondante. `PUBLIC_WHATSAPP` utilise le format international sans espace ni signe `+`. Après modification, exécuter `php artisan config:clear`.

## Règles à préserver

- Ne pas ajouter d’emoji dans les textes, boutons ou titres.
- Ne pas présenter de chiffre, certification, adresse ou contact non validé.
- Ne pas exposer de donnée privée dans une page publique.
- Ne pas activer une inscription publique.
- Conserver des textes alternatifs descriptifs pour les images utiles.
