# Contrôle des identifiants exposés

Le dépôt a été public avec des secrets dans son historique. Réécrire
l'historique ne suffit pas : tout ce qui a été moissonné pendant cette
période l'est définitivement. Seule la révocation rend la copie inutile.

Ces deux scripts ne révoquent rien — ils **tentent réellement de se servir**
de chaque identifiant et rapportent ce que le fournisseur répond.

```sh
node outils/verifier-revocation.mjs          # Render, Google, Cloudinary
php  outils/verifier-ancienne-base.php       # ancienne base Neon
```

Aucun secret n'est affiché : seuls le verdict et le motif du refus le sont.

## Lecture des verdicts

| Marque | Sens |
|---|---|
| `✗` | l'identifiant fonctionne toujours — rien n'est réglé |
| `✓` | le fournisseur a refusé |
| `·` | le contrôle n'a pas abouti ; une panne réseau n'est pas une révocation |

La marque vient d'un champ `gravite` posé à chaque constat, jamais déduite du
libellé : « incontrôlable » ne contient pas « contrôler », et une ligne non
vérifiée s'affichait en vert.

## Un compte neuf ne révoque pas l'ancien

Le piège s'est présenté deux fois. Créer une base Neon neuve laisse l'ancienne
en service ; ouvrir un cloud Cloudinary neuf laisse l'ancien ouvert. Ce qui
compte est la suppression ou la désactivation **de l'ancien**, pas la création
du remplaçant.

Le contrôle Cloudinary vise donc les identifiants exposés inscrits dans le
script, et non ce que porte le fichier au moment où on le lance. Une première
version lisait `back/.env.local` : le jour où ces valeurs y ont été remplacées
par celles d'un compte neuf, elle a testé le nouveau compte tout en affichant
« encore valides ».

Le script distingue l'extinction du service Node de sa présence en cherchant
sa réponse propre — `{"error":"Route non trouvée"}`, du JSON en français.
La page d'un service éteint chez l'hébergeur est du HTML : la confusion entre
les deux ferait conclure à tort.

## Piège sur la clé de service Google

Un refus `invalid_grant` peut venir d'une clé révoquée **ou** d'une horloge
locale décalée de plus de cinq minutes, qui rend l'assertion JWT invalide.
Avant de conclure à une révocation, comparer l'heure locale à l'en-tête
`Date` renvoyé par `oauth2.googleapis.com`.

## Ce qui reste à faire, et où

| Identifiant | État | Console | Repère |
|---|---|---|---|
| Service Node | **en ligne** | dashboard.render.com | `e-com-back` → Settings → Suspend |
| Ancien cloud Cloudinary | à constater | console.cloudinary.com | cloud `dffo9wq7x`, clé `822124857229833` |
| Compte de service Firebase | révoqué | — | `firebase-adminsdk-fbsvc@e-com-ea8e8` ne répond plus |
| Ancienne base Neon | révoquée | — | `password authentication failed` |

Le cloud en service est désormais `fvimkhoh`, déclaré dans `api/.env`. Les
identifiants avaient d'abord été posés dans `back/.env.local`, le fichier du
backend Node retiré, que Laravel ne lit pas : l'envoi de visuels restait
inopérant.
