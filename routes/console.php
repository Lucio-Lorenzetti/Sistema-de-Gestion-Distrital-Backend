<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mantiene la base de Supabase activa (plan gratuito la pausa tras 7 días sin
// actividad) — falta configurar quién dispara schedule:run en producción.
Schedule::command('db:heartbeat')->daily();
