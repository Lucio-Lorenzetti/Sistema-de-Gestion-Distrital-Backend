# Despliegue del backend en Vercel (solución transitoria)

El backend corre como función serverless con el runtime comunitario
[`vercel-php`](https://github.com/vercel-community/php) (PHP 8.2). La base de datos y los
archivos siguen en Supabase. No hace falta tarjeta: es el plan gratuito de Vercel.

Cómo está armado:

- `vercel.json`: usa `vercel-php@0.6.2` y manda todos los pedidos a `api/index.php`.
- `api/index.php`: punto de entrada. Redirige lo que Laravel escribe (logs, vistas
  compiladas, manifiestos de paquetes) al directorio temporal, porque el resto del sistema
  de archivos es de solo lectura, y le hace creer a Laravel que cuelga de la raíz.
- `.vercelignore`: deja afuera tests, docs y secretos.

## Limitaciones que hay que conocer

- Es un runtime **no oficial** de Vercel: puede romperse si Vercel cambia algo.
- No hay disco permanente. Todo archivo subido va a Supabase (`UPLOADS_DISK=s3`).
- No hay procesos en segundo plano ni scheduler (ya se resolvía con cron-job.org).
- El primer pedido tras un rato sin uso es más lento (arranca PHP y Laravel).
- El plan Hobby es para uso personal no comercial: revisá la política de uso justo de Vercel.

## 1. Crear el proyecto en Vercel

1. **Add New > Project** e importá el repositorio del **backend**. Tiene que ser un proyecto
   aparte del frontend.
2. Framework Preset: **Other**. Dejá vacíos Build Command y Output Directory.
3. Antes de desplegar, cargá las variables de entorno (paso 2).

## 2. Variables de entorno

El archivo `vercel.env.example` tiene todas las variables listas para pegar: copialo a un archivo
local fuera de Git (por ejemplo `.env.production`, que está ignorado), completá los valores `<...>` y
pegá todo el contenido en el primer campo de **Environment Variables** de Vercel (las separa solo).

Los valores salen de lo que ya tenías configurado (el panel de Render, si todavía lo ves; si
no, de Supabase, Brevo y Google AI Studio).

| Variable | Valor / nota |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | El de antes. Si no lo tenés, generá uno con `php artisan key:generate --show` (solo se invalidan cookies y enlaces de recuperación pendientes) |
| `APP_URL` | La URL del proyecto en Vercel, sin barra final |
| `FRONTEND_URL` | `https://distrito3zona13.vercel.app` |
| `CORS_ALLOWED_ORIGINS` | `https://distrito3zona13.vercel.app` (varios, separados por coma) |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Cadena del **pooler** de Supabase (la conexión directa es solo IPv6 y Vercel sale por IPv4) |
| `DB_SSLMODE` | `require` |
| `DB_DISABLE_PREPARES` | `true` si usás el pooler en modo transacción (puerto 6543). **No uses `DB_EMULATE_PREPARES`**: rompe las consultas con booleanos (`boolean = integer`) |
| `CACHE_STORE` | `database` |
| `SESSION_DRIVER` | `array` (la API usa tokens, no sesiones) |
| `QUEUE_CONNECTION` | `sync` |
| `UPLOADS_DISK` | `s3` (obligatorio) |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | Supabase Storage (S3), los mismos de antes |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Brevo, igual que antes |
| `GEMINI_API_KEY`, `GEMINI_MODEL` | Asistente de IA |
| `CRON_SECRET` | El mismo secreto que usa cron-job.org |

## 3. Primer despliegue y chequeos

1. Desplegá. Cuando termine, abrí `https://<proyecto>.vercel.app/up`: tiene que dar 200.
2. Abrí `https://<proyecto>.vercel.app/api/news`: tiene que devolver JSON.
3. Si ves una página de login de Vercel en vez de JSON, desactivá la protección en
   **Settings > Deployment Protection** de este proyecto.
4. Si falla, mirá **Logs** del despliegue: el error de configuración aparece ahí.

## 4. Apuntar el resto del sistema al backend nuevo

1. **Proyecto del frontend en Vercel:** cambiá `VITE_API_URL` a `https://<proyecto>.vercel.app/api`
   y redesplegá. Opcional: `VITE_PANEL_BACKEND_URL` y `VITE_PANEL_FRONTEND_URL` con los enlaces
   a los paneles de cada proyecto, para el acceso rápido del Dashboard del Developer.
2. **cron-job.org:** cambiá la URL a
   `https://<proyecto>.vercel.app/api/db-heartbeat-sistema/<CRON_SECRET>`.
3. **Datos de los grupos:** `php artisan db:seed --class=GrupoSeeder`, ejecutado desde tu
   máquina contra la base de producción. Antes verificá que los nombres de la tabla `grupos`
   coincidan con los del seeder (si no, se crean duplicados).

## 5. Probar de punta a punta

- Iniciar sesión y recorrer el panel.
- Registrar una cuenta de prueba y abrir el enlace de verificación del correo.
- Subir una foto de perfil, una foto de grupo, un documento y una imagen de noticia.
- Descargar el PDF de un programa.
- Usar el asistente de IA.
- Dashboard del Developer: "Estado del Sistema" debe mostrar la base y el último cron.

## Para revisar si algo falla

- **500 al descargar el PDF:** suele ser una vista que no pudo compilarse; mirá los logs.
- **Errores de conexión a la base o "prepared statement does not exist":** falta
  `DB_DISABLE_PREPARES=true` con el pooler en modo transacción.
- **`operator does not exist: boolean = integer`:** quedó configurado `DB_EMULATE_PREPARES`. Borralo y
  usá `DB_DISABLE_PREPARES=true`.
- **Los mails no salen:** mirá "Errores Recientes" en el Dashboard del Developer.
- **CORS:** el dominio del frontend tiene que estar en `CORS_ALLOWED_ORIGINS`, sin barra final.
