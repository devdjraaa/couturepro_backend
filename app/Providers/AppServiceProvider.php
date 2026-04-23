<?php

namespace App\Providers;

use App\Models\NiveauConfig;
use App\Observers\NiveauConfigObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        NiveauConfig::observe(NiveauConfigObserver::class);
    }
}
