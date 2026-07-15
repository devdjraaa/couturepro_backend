<?php

namespace App\Providers;

use App\Models\NiveauConfig;
use App\Models\NotificationSysteme;
use App\Observers\NiveauConfigObserver;
use App\Observers\NotificationSystemeObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Compatibilité MySQL 5.7 / MariaDB (utf8mb4 + index VARCHAR)
        Schema::defaultStringLength(191);

        NiveauConfig::observe(NiveauConfigObserver::class);
        NotificationSysteme::observe(NotificationSystemeObserver::class);

        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory())->create(
                new Dsn('brevo+api', 'default', config('mail.mailers.brevo.key'))
            );
        });

        // P150 : Apple Sign In via socialiteproviders/apple (Google/Facebook sont natifs Socialite).
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });
    }
}
