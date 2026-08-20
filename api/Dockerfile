# syntax=docker/dockerfile:1

# =====================================================================
# API Laravel — image de production
#
# FrankenPHP plutôt que php-fpm + nginx : un seul processus à surveiller,
# et le serveur sert directement public/. `artisan serve` n'a pas sa place
# ici, c'est un serveur de développement mono-requête.
# =====================================================================

FROM dunglas/frankenphp:1-php8.4 AS base

# Composer n'est pas fourni par l'image FrankenPHP : sans cette copie, la
# construction s'arrête sur « composer: not found ». Le binaire officiel
# plutôt qu'une installation par script — sa version s'en trouve figée.
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# Composer ne réclame que ctype, dom, fileinfo, filter, hash, iconv, json,
# libxml, mbstring, openssl, pcre, session et tokenizer — tous présents dans
# l'image. Deux manquent pourtant à l'appel :
#
#   pdo_pgsql — la base est sur Neon ; aucune dépendance ne l'exige, mais
#               sans elle l'application ne se connecte à rien.
#   gd        — MediaService instancie Intervention\Image\Drivers\Gd\Driver
#               pour redimensionner les visuels avant envoi à Cloudinary.
#
# opcache et zip ne sont pas nécessaires au fonctionnement, mais l'un évite
# de recompiler le code à chaque requête et l'autre accélère Composer.
RUN install-php-extensions \
        pdo_pgsql \
        gd \
        opcache \
        zip \
    && apt-get update \
    && apt-get install -y --no-install-recommends git \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# ---------------------------------------------------------------------
# Dépendances d'abord, code ensuite : tant que composer.lock ne bouge pas,
# cette couche est réutilisée et le déploiement ne réinstalle rien.
# ---------------------------------------------------------------------
COPY composer.json composer.lock ./

# Pas de `--mount=type=cache` : cette syntaxe exige BuildKit, et rien ne
# garantit qu'il soit actif chez l'hébergeur. Le gain ne vaut pas le risque
# de voir la construction échouer sur une directive non comprise.
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

COPY . .

# `--no-scripts` plus haut évitait que les scripts tournent sans le code ;
# ils sont lancés maintenant, une fois l'application complète.
# La découverte des paquets (`post-autoload-dump`) n'a pas lieu ici : elle
# démarre Laravel, or aucune variable d'environnement n'est posée pendant la
# construction. Elle est faite au lancement, par docker-entrypoint.sh.
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Les caches de configuration et de routes ne sont PAS construits ici :
# `config:cache` fige les variables d'environnement au moment de la
# construction, or elles ne sont connues qu'au démarrage sur l'hébergeur.
# Ils sont produits par docker-entrypoint.sh.

# Chemins écrits un par un : `/bin/sh` sur Debian est dash, qui ne fait pas
# l'expansion d'accolades. `{cache,sessions,views}` aurait produit un dossier
# portant ce nom littéral, et Laravel aurait refusé de démarrer faute de
# chemin de cache valide — sans que la construction, elle, échoue.
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV SERVER_NAME=:8080 \
    APP_ENV=production \
    APP_DEBUG=false \
    OCTANE_SERVER=frankenphp

EXPOSE 8080

COPY docker-entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
