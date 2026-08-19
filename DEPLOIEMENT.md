# Mise en production

Deux services, décrits par `render.yaml` : l'API Laravel et le site Vue en
statique. **Aucun n'est payant.**

Render n'a pas de runtime PHP natif — l'API passe donc par une image Docker
(`api/Dockerfile`), bâtie sur FrankenPHP. `artisan serve` n'a pas sa place en
production : c'est un serveur de développement qui traite une requête à la
fois.

## Avant le premier déploiement

**Générer la clé d'application.** En local :

```sh
php artisan key:generate --show
```

Coller la valeur dans `APP_KEY`. La changer plus tard rend illisible tout ce
qui a été chiffré avant — sessions comprises.

**Renseigner les valeurs marquées `sync: false`** dans l'interface de Render :
`DB_URL`, les `MAIL_*`, les `CLOUDINARY_*`. Elles ne sont pas versionnées.

`GOOGLE_CLIENT_ID` et `GOOGLE_CLIENT_SECRET` peuvent rester vides : l'écran de
connexion actuel ne propose pas Google.

## Ce que le blueprint règle déjà

**Les migrations** sont lancées au démarrage du conteneur, sous
`RUN_MIGRATIONS`. Leur place normale est un `preDeployCommand`, mais c'est une
fonction des plans payants. Ce n'est acceptable que parce que l'offre gratuite
ne donne qu'une seule instance : avec plusieurs, chacune les exécuterait en
parallèle.

**Les caches** de configuration, de routes et de vues sont construits au
démarrage du conteneur, pas à la construction de l'image : `config:cache` fige
les variables d'environnement, qui ne sont connues qu'au lancement. Une image
bâtie avec les valeurs d'un autre environnement viserait la mauvaise base.

**Le routage du site.** Le routeur Vue est en mode history : `/catalogue` n'a
pas de fichier correspondant. La règle de réécriture renvoie tout vers
`index.html`, sans quoi chaque lien partagé rendrait 404.

**Les origines.** `FRONTEND_URL` est déduite du service du site et lue par
`config/cors.php`. Seule cette origine peut appeler l'API depuis un
navigateur.

## Points de vigilance

**PgBouncer.** Le point d'accès Neon `-pooler` ne supporte pas les requêtes
préparées nommées. `DB_EMULATE_PREPARES=true` est indispensable : sans lui les
migrations échouent en `SQLSTATE[25P02]`.

**Pas de worker, donc pas de file d'attente.** Render ne propose aucune offre
gratuite pour les processus d'arrière-plan. `QUEUE_CONNECTION` vaut donc
`sync` : les courriels partent dans la requête elle-même.

Conséquence à connaître : la campagne aux abonnés boucle sur toute la liste en
synchrone et **expirera passé quelques dizaines d'inscrits**. Les courriels
unitaires — vérification d'adresse, réinitialisation de mot de passe — ne
posent aucun problème.

Pour rétablir la file : `QUEUE_CONNECTION=database`, et redéclarer un service
`worker` en plan payant.

**Le cache est en fichier**, propre à chaque instance et remis à zéro à chaque
déploiement. C'est voulu : ce cache existe pour éviter des allers-retours vers
Neon, l'y héberger n'aurait aucun intérêt. En revanche, avec plusieurs
instances, l'invalidation faite par l'une ne touche pas les autres — le
catalogue peut rester périmé jusqu'à cinq minutes sur les instances qui n'ont
pas reçu la commande. Passer à Redis le jour où le service dépasse une
instance.

**Le point de santé** est `/up`, qui ne touche pas la base : un incident Neon
ne doit pas faire redémarrer en boucle un service par ailleurs sain.

## Ce qui n'a pas été vérifié

L'image Docker n'a pas été construite : Docker n'est pas installé sur le poste
de développement. Ont été vérifiés en revanche la liste exacte des extensions
PHP exigées (`composer check-platform-reqs --no-dev`, complétée de `pdo_pgsql`
et `gd`, que le code utilise sans que Composer les réclame), le bon
fonctionnement de l'application avec les caches de production actifs, et la
configuration CORS.

Attendez-vous à un ou deux ajustements au premier `docker build`.

## Après la bascule

Le service Node `e-com-back` doit être suspendu — il tourne encore, avec ses
secrets d'environnement, et sert une application qui n'a plus de base.
`outils/verifier-revocation.mjs` le confirmera.

## Les valeurs à saisir dans Render

| Clé | Où | Exemple |
|---|---|---|
| `APP_KEY` | groupe commun | sortie de `php artisan key:generate --show` |
| `DB_URL` | groupe commun | chaîne Neon complète |
| `MAIL_HOST` `MAIL_USERNAME` `MAIL_PASSWORD` `MAIL_FROM_ADDRESS` | groupe commun | votre fournisseur SMTP |
| `CLOUDINARY_*` | groupe commun | les trois valeurs du cloud en service |
| `APP_URL` | API | `https://goldshop-api.onrender.com` |
| `FRONTEND_URL` | API | `https://goldshop-site.onrender.com` |
| `VITE_API_BASE` | site | `https://goldshop-api.onrender.com` |

Les trois dernières s'écrivent **avec `https://`**. Render sait bien injecter
l'adresse d'un service dans un autre, mais `property: host` rend un nom d'hôte
nu : une base d'API sans schéma serait lue comme un chemin relatif, et une
origine sans schéma ne correspondrait à aucun en-tête `Origin` — l'API se
fermerait au navigateur sans message clair.

`SANCTUM_STATEFUL_DOMAINS` reste déduite automatiquement : Sanctum attend
justement un domaine nu.

Les adresses ne sont connues qu'après la première création des services. Le
premier déploiement échouera donc côté site — renseignez les trois valeurs,
puis relancez.

## Le prix du gratuit

**L'API s'endort** après une période sans trafic. Le visiteur suivant attend
son réveil — de l'ordre de la minute pour une image Docker PHP. C'est la
première impression de la boutique qui en pâtit.

Passer `plan: free` à `plan: starter` sur `goldshop-api` le jour où de vrais
clients arrivent : le réveil à froid coûte plus cher en ventes perdues qu'en
abonnement. Remettre alors les migrations dans un `preDeployCommand` et
`RUN_MIGRATIONS` à `false`.

Le site statique, lui, est gratuit sans réserve ni réveil.
