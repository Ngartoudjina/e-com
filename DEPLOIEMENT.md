# Mise en production

Trois services, décrits par `render.yaml` : l'API Laravel, un worker de file
d'attente, et le site Vue en statique.

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

**Les migrations** tournent en `preDeployCommand`, une fois par déploiement,
avant que la nouvelle version ne reçoive du trafic. Lancées au démarrage du
conteneur, chaque instance les exécuterait en parallèle.

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

**Le worker n'est pas décoratif.** Les envois groupés aux abonnés passent par
`Mail::queue`. Sans lui, ils s'empilent dans la table `jobs` et ne partent
jamais — sans qu'aucune erreur ne le signale.

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
