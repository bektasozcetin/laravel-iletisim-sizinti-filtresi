<?php

declare(strict_types=1);

namespace Bektasozcetin\IletisimFiltresi;

use Illuminate\Support\ServiceProvider;

final class IletisimFiltresiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/iletisim-filtresi.php', 'iletisim-filtresi');

        $this->app->singleton(IletisimFiltresi::class, fn ($app) => new IletisimFiltresi(
            $app['config']->get('iletisim-filtresi.turler'),
            $app['config']->get('iletisim-filtresi.maske', '[iletişim gizli]'),
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'iletisim-filtresi');

        $this->publishes([
            __DIR__.'/../config/iletisim-filtresi.php' => $this->app->configPath('iletisim-filtresi.php'),
        ], 'iletisim-filtresi-config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/iletisim-filtresi'),
        ], 'iletisim-filtresi-lang');
    }
}
