# Identité visuelle d'ABIWEIN

## 1. Intention

L'identité d'ABIWEIN associe l'élevage avicole, la gestion agricole et l'analyse numérique. Elle doit rester sobre, naturelle, rassurante et lisible. Le vert traduit la croissance et l'environnement agricole ; les tons beiges apportent chaleur et équilibre ; le bleu foncé et le doré présents dans le logo évoquent respectivement la fiabilité technologique et la prospérité.

## 2. Palette officielle

| Jeton recommandé | Couleur | Code | Usage principal |
|---|---|---:|---|
| `brand-olive` | Vert olive foncé | `#31380F` | navigation, titres, texte fort, boutons principaux |
| `brand-sage` | Vert sauge | `#798057` | éléments secondaires, bordures, états calmes |
| `brand-beige-light` | Beige clair | `#E2D4B9` | arrière-plans doux, surfaces secondaires |
| `brand-beige-taupe` | Beige taupe | `#C7AF97` | séparateurs, encadrés, accents neutres |
| `brand-beige-rose` | Beige rosé | `#D8C7B5` | états chaleureux, arrière-plans ponctuels |

Les codes hexadécimaux sont normatifs. Ne pas les remplacer par une couleur visuellement proche.

### Règles d'emploi

- Utiliser le vert olive foncé pour le texte sur fond clair.
- Réserver le vert sauge aux éléments secondaires ; ne pas l'utiliser automatiquement pour de longs textes sans contrôle de contraste.
- Utiliser les beiges comme surfaces et accents, pas comme couleur de texte principal.
- Préserver des surfaces blanches afin de garder l'interface légère et lisible.
- Les couleurs dorée et bleu foncé du logo appartiennent au fichier officiel. Elles ne deviennent pas des jetons d'interface sans validation ultérieure.
- Un état ne doit jamais être communiqué par la couleur seule : ajouter un libellé, une icône ou un motif.

## 3. Logo officiel

Le logo combine :

- un poulet, représentant l'activité avicole ;
- des points reliés, représentant l'analyse par l'intelligence artificielle et les données ;
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
- Le bouton principal utilise le vert olive foncé avec un texte clair dont le contraste est vérifié.
- Les messages de succès, d'avertissement et d'erreur peuvent employer des couleurs fonctionnelles supplémentaires, clairement identifiées comme telles et conformes aux exigences de contraste.
- Le focus clavier doit être visible.
- Le corps de texte vise une taille minimale de 16 px sur téléphone.
- Les graphiques Chart.js doivent proposer une légende ou une valeur textuelle ; leur compréhension ne doit pas dépendre uniquement des couleurs.

## 6. Contrôle avant livraison

- Logo officiel, net, proportionné et entouré d'espace libre.
- Cinq couleurs exactes présentes dans les variables de thème.
- Police Roboto avec une pile de repli.
- Contraste des textes et contrôles vérifié.
- Mise en page testée sur téléphone, tablette et ordinateur.
- Aucun nom `Abivai` ou autre variante : la marque s'écrit toujours **ABIWEIN**.
