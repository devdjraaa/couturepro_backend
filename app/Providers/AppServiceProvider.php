<?php

namespace App\Providers;

use App\Models\NiveauConfig;
use App\Observers\NiveauConfigObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Compatibilité MySQL 5.7 / MariaDB (utf8mb4 + index VARCHAR)
        Schema::defaultStringLength(191);

        NiveauConfig::observe(NiveauConfigObserver::class);
    }
}
