<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Détecte les en-têtes envoyés par Cloudflare / Localtunnel / Ngrok
        if ($forwardedHost = request()->header('x-forwarded-host')) {
            // Récupère uniquement le premier hôte si une liste séparée par des virgules est envoyée
            $host = trim(explode(',', $forwardedHost)[0]);
            $proto = request()->header('x-forwarded-proto', 'https');

            URL::forceRootUrl("{$proto}://{$host}");
            URL::forceScheme('https');
        }
    }
}
