#!/bin/sh
set -e

# Las variables de entorno reales (DB, mail, etc.) las inyecta Render recién al
# arrancar el contenedor, así que el cacheo de config/rutas/eventos se hace acá
# (en runtime) y no en el Dockerfile (en build). Si falla (p. ej. primer deploy
# sin algunas env vars todavía), seguimos sin cachear en vez de no levantar.
php artisan config:cache || true
php artisan route:cache || true
php artisan event:cache || true

exec apache2-foreground
