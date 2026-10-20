<?php

/**
 * Punto de entrada de Laravel en Vercel (runtime vercel-php).
 *
 * En Vercel el código se despliega en un sistema de archivos de SOLO LECTURA: lo
 * único escribible es el directorio temporal. Laravel necesita escribir logs,
 * vistas compiladas (las usa el PDF de los programas) y los manifiestos de
 * paquetes/servicios, así que se los redirige al directorio temporal ANTES de
 * arrancar la aplicación. Cada instancia empieza con ese directorio vacío: no hay
 * que guardar nada importante ahí (los archivos subidos van a Supabase, ver UPLOADS_DISK).
 */

$tmp = rtrim(sys_get_temp_dir(), '/\\');

$rutas = [
    'LARAVEL_STORAGE_PATH' => "$tmp/storage",
    'APP_PACKAGES_CACHE' => "$tmp/bootstrap-cache/packages.php",
    'APP_SERVICES_CACHE' => "$tmp/bootstrap-cache/services.php",
    'APP_CONFIG_CACHE' => "$tmp/bootstrap-cache/config.php",
    'APP_ROUTES_CACHE' => "$tmp/bootstrap-cache/routes-v7.php",
    'APP_EVENTS_CACHE' => "$tmp/bootstrap-cache/events.php",
];

foreach ($rutas as $clave => $valor) {
    // Si alguien las define a mano en las variables de entorno de Vercel, se respetan.
    if (getenv($clave) === false && ! isset($_ENV[$clave])) {
        putenv("$clave=$valor");
        $_ENV[$clave] = $valor;
        $_SERVER[$clave] = $valor;
    }
}

$directorios = [
    "$tmp/bootstrap-cache",
    "$tmp/storage/app/public",
    "$tmp/storage/framework/cache/data",
    "$tmp/storage/framework/sessions",
    "$tmp/storage/framework/views",
    "$tmp/storage/logs",
];

foreach ($directorios as $directorio) {
    if (! is_dir($directorio)) {
        @mkdir($directorio, 0777, true);
    }
}

// Este archivo vive en /api, pero Laravel tiene que verse como si estuviera en la raíz
// del sitio: si no, interpreta "/api" como la dirección base de la aplicación, la descarta
// del camino, y las rutas /api/... (que son las de esta API) dan 404.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = realpath(__DIR__ . '/../public/index.php');

require __DIR__ . '/../public/index.php';
