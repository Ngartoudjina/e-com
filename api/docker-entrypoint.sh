#!/bin/sh
set -e

# =====================================================================
# Démarrage du conteneur.
#
# Les caches sont construits ici et non à la construction de l'image :
# `config:cache` fige les variables d'environnement, qui ne sont connues
# qu'au lancement chez l'hébergeur. Une image construite avec les valeurs
# d'un autre environnement viserait la mauvaise base de données.
# =====================================================================

# L'hébergeur impose le port par $PORT et interroge celui-là. FrankenPHP,
# lui, écoute ce que dit SERVER_NAME. Figé à 8080, le conteneur démarrait
# correctement mais ne répondait jamais sur le port surveillé : construction
# réussie, service déclaré défaillant.
export SERVER_NAME=":${PORT:-8080}"
echo "Écoute sur ${SERVER_NAME}"

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY est vide : les données chiffrées et les sessions seraient illisibles." >&2
    echo "Générer une clé avec « php artisan key:generate --show » et la déclarer." >&2
    exit 1
fi

# La découverte des paquets a été retirée de la construction : elle démarre
# Laravel, qui n'a alors aucune variable d'environnement. Sa place est ici.
php artisan package:discover --ansi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Les migrations appartiennent normalement à une étape de pré-déploiement :
# lancées au démarrage du conteneur, plusieurs instances les exécuteraient en
# parallèle. Mais `preDeployCommand` est réservé aux plans payants de Render.
#
# Sous RUN_MIGRATIONS, elles ont donc lieu ici. Ce n'est acceptable que parce
# que l'offre gratuite ne donne qu'une seule instance. Repasser au
# pré-déploiement dès que le service monte en charge.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Migrations en cours…"
    php artisan migrate --force
fi

exec "$@"
