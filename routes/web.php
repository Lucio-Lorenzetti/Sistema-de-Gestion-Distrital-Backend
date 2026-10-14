<?php

use Illuminate\Support\Facades\Route;

// Route::view (no Closure) para que `php artisan route:cache` pueda cachear esta ruta.
Route::view('/', 'welcome');
