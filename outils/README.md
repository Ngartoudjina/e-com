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

| Verdict | Sens |
|---|---|
| `ENCORE EN LIGNE` / `ENCORE VALIDE` | l'identifiant fonctionne toujours — rien n'est réglé |
| `révoqué` / `éteint` | le fournisseur a refusé |
| `indéterminé` | le contrôle n'a pas abouti ; une panne réseau n'est pas une révocation |

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

| Identifiant | Console | Repère |
|---|---|---|
| Service Node | dashboard.render.com | service `e-com-back` → Settings → Suspend |
| Clés Cloudinary | console.cloudinary.com | cloud `dffo9wq7x`, clé `822124857229833` |
| Ancienne base Neon | console.neon.tech | projet `ep-green-glade-aybjulvp` |

Le compte de service Firebase `firebase-adminsdk-fbsvc@e-com-ea8e8` ne
répond plus : il est révoqué.
