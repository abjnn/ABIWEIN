# Identité visuelle d'ABIWEIN

## 1. Intention

L'identité d'ABIWEIN associe l'élevage avicole, la fiabilité et le suivi des données. Elle doit rester sobre, professionnelle, rassurante et très lisible. Le blanc domine toutes les interfaces pour conserver de l'espace et de la clarté ; un noir doux structure le tableau de bord, le vert forêt signale les actions, le vert sauge accompagne les éléments secondaires, l'ivoire adoucit les surfaces et l'or ponctue les données importantes.

## 2. Palette officielle

| Jeton recommandé | Couleur | Code | Usage principal |
|---|---|---:|---|
| `brand-forest` | Vert forêt | `#234B35` | navigation, titres, texte fort et boutons principaux |
| `brand-sage` | Vert sauge | `#6F835D` | éléments secondaires, repères, bordures et focus |
| `brand-white` | Blanc | `#FFFFFF` | fond dominant, cartes, formulaires et zones de respiration |
| `brand-ivory` | Ivoire | `#F2EBDD` | fonds secondaires et séparations douces |
| `brand-gold` | Or discret | `#C99A2E` | accents, filets et séries complémentaires des graphiques |

Les codes hexadécimaux sont normatifs. Ne pas les remplacer par une couleur visuellement proche.

### Règles d'emploi

- Garder le blanc comme couleur dominante des pages, cartes, formulaires et menus.
- Employer un noir doux ou un gris très foncé pour les textes et la hiérarchie du tableau de bord, jamais un noir massif sur de grandes surfaces.
- Utiliser le vert forêt pour le texte sur fond clair, les liens, la navigation et les actions principales.
- Réserver le vert sauge aux éléments secondaires, repères, bordures et focus ; ne pas l'utiliser avec du blanc pour un petit texte.
- Employer l'ivoire pour les surfaces secondaires en conservant le vert forêt pour le contenu textuel.
- Réserver l'or aux accents et à la différenciation visuelle des données ; ne pas l'utiliser seul pour du texte sur fond blanc.
- Employer uniquement des opacités de ces cinq couleurs pour les nuances, sans introduire une couleur décorative supplémentaire.
- Les couples vert forêt/blanc et vert forêt/ivoire offrent respectivement des contrastes d'environ `9,86:1` et `8,31:1`. Le vert sauge et l'or restent décoratifs ou associés à un libellé lorsque leur contraste est insuffisant.
- Les autres couleurs du logo restent propres au fichier officiel et ne deviennent pas des jetons d'interface sans validation ultérieure.
- Un état ne doit jamais être communiqué par la couleur seule : ajouter un libellé, une icône ou un motif.

## 3. Logo officiel

Le logo combine :

- un poulet, représentant l'activité avicole ;
- des points reliés, représentant les données et leur suivi continu ;
- un cercle, représentant la centralisation et le suivi continu ;
- des feuilles et des épis, représentant l'environnement agricole ;
- le nom **ABIWEIN** en capitales.

Le fichier de référence de l'application est `public/brand/abiwein-logo.png`. Lorsqu'une autre déclinaison devient nécessaire, elle doit être produite à partir de la source officielle et non d'une capture compressée.

### Utilisation correcte

- Conserver les proportions du logo ; ne jamais l'étirer horizontalement ou verticalement.
- Conserver toutes ses couleurs et ne pas recolorer un élément isolé.
- Ne pas pivoter, ombrer, détourer ou ajouter d'effet.
- Ne pas modifier le texte ABIWEIN ni le séparer du symbole dans la version complète.
- Le placer sur un fond blanc ou très clair, sans motif derrière lui.
- Garder autour du logo un espace libre au moins égal à la hauteur de la lettre `A` du mot-symbole.
- Utiliser l'image à sa résolution native ou inférieure ; ne pas l'agrandir au point de faire apparaître les pixels.

Pour un affichage très petit, utiliser une future version de pictogramme officiellement validée plutôt que rendre le mot-symbole illisible. En attendant cette déclinaison, conserver le logo complet et lui donner une largeur suffisante.

### Taille minimale conseillée

- écran : 120 px de large pour le logo complet ;
- document imprimé : 30 mm de large.

Ces minima pourront être ajustés après des essais sur les supports réels, sans altérer les proportions.

## 4. Typographie

La famille principale est **Roboto**. Prévoir une pile de repli locale afin que l'interface reste utilisable même si la police distante ne charge pas :

```css
font-family: Roboto, system-ui, -apple-system, "Segoe UI", sans-serif;
```

- Titres : Roboto 600 ou 700.
- Texte courant : Roboto 400.
- Libellés et boutons : Roboto 500 ou 600.
- Éviter les capitales sur de longues phrases ; les réserver aux acronymes et au mot-symbole officiel.

## 5. Interface

- Les composants doivent conserver un rayon, des espacements et des états cohérents dans toute l'application.
- Le tableau de bord privilégie le blanc, le texte presque noir, des bordures grises légères et quelques accents verts ou or ; il évite les grands aplats et les rectangles décoratifs inutiles.
- Le bouton principal utilise le vert forêt avec un texte blanc dont le contraste est vérifié.
- Les messages de succès, d'avertissement et d'erreur peuvent employer des couleurs fonctionnelles supplémentaires, clairement identifiées comme telles et conformes aux exigences de contraste.
- Le focus clavier doit être visible.
- Le corps de texte vise une taille minimale de 16 px sur téléphone.
- Les graphiques Chart.js doivent proposer une légende ou une valeur textuelle ; leur compréhension ne doit pas dépendre uniquement des couleurs.

## 6. Contrôle avant livraison

- Logo officiel, net, proportionné et entouré d'espace libre.
- Cinq couleurs exactes présentes dans les variables de thème, avec le blanc dominant.
- Police Roboto avec une pile de repli.
- Contraste des textes et contrôles vérifié.
- Mise en page testée sur téléphone, tablette et ordinateur.
- Aucun nom `Abivai` ou autre variante : la marque s'écrit toujours **ABIWEIN**.
