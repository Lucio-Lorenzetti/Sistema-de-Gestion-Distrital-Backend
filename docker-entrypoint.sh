#!/bin/sh
set -e

# Las variables de entorno reales (DB, mail, etc.) las inyecta la plataforma de
# hosting recién al arrancar el contenedor, así que el cacheo de config/rutas/eventos se hace acá
# (en runtime) y no en el Dockerfile (en build). Si falla (p. ej. primer deploy
# sin algunas env vars todavía), seguimos sin cachear en vez de no levantar.
php artisan config:cache || true
php artisan route:cache || true
php artisan event:cache || true

# Las plataformas de hosting (Cloud Run, Render, etc.) indican en $PORT el puerto
# en el que tiene que escuchar el contenedor; Apache escucha en el 80 por defecto.
if [ -n "${PORT:-}" ] && [ "$PORT" != "80" ]; then
    sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/*.conf
fi

exec apache2-foreground
