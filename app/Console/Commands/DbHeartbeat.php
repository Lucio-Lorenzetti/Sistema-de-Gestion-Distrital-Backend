<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DbHeartbeat extends Command
{
    protected $signature = 'db:heartbeat';

    protected $description = 'Escribe en la tabla cache para que Supabase no pause el proyecto por inactividad';

    public function handle(): int
    {
        Cache::put('db_heartbeat', now()->toIso8601String(), now()->addDays(2));

        $this->info('Heartbeat registrado: ' . now()->toIso8601String());

        return self::SUCCESS;
    }
}
