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

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY est vide : les données chiffrées et les sessions seraient illisibles." >&2
    echo "Générer une clé avec « php artisan key:generate --show » et la déclarer." >&2
    exit 1
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Les migrations ne sont pas lancées ici : avec plusieurs instances, chacune
# les exécuterait en même temps. Elles appartiennent à l'étape de
# pré-déploiement (voir preDeployCommand dans render.yaml).

exec "$@"
