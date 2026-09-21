#!/usr/bin/env sh
set -eu

# Render fournit un port dynamique ; Apache doit écouter exactement cette valeur.
ABIWEIN_PORT="${PORT:-10000}"
sed -ri "s/^Listen [0-9]+/Listen ${ABIWEIN_PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${ABIWEIN_PORT}>/" /etc/apache2/sites-available/000-default.conf

# L'URL publique injectée par Render devient l'URL de référence de Laravel.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="${RENDER_EXTERNAL_URL}"
fi

# Les dossiers volatils sont recréés à chaque démarrage avec les droits nécessaires.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# La configuration est figée après injection des secrets de l'hébergeur.
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Le schéma est mis à niveau, puis une base vide seulement reçoit les données initiales.
php artisan migrate --force --no-interaction
php artisan abiwein:bootstrap --no-interaction

# Apache reste le processus principal afin que Render puisse superviser le service.
exec apache2-foreground
