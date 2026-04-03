<?php

namespace App\Providers;

use App\Models\Program; // Importá el Modelo
use App\Policies\ProgramPolicy; // Importá la Policy
use Illuminate\Support\Facades\Gate; // Importá el Gate
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Program::class, ProgramPolicy::class);
    }
}