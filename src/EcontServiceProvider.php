<?php
namespace Rosshristov\Econt;

use Illuminate\Support\ServiceProvider;
use Rosshristov\Econt\Commands\Sync;

/**
 * EcontServiceProvider for Laravel 5.1+
 *
 * @package    Rosshristov\Econt
 * @version    1.0
 */
class EcontServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/econt.php' => config_path('econt.php')], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
//        $this->publishes([__DIR__ . '/../database/migrations/' => database_path('migrations')], 'migrations');

        $this->mergeConfigFrom(__DIR__ . '/../config/econt.php', 'econt');

        $this->loadTranslationsFrom($this->app->basePath(). '/vendor/rolice/econt/resources/lang', 'econt');

        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/Http/routes.php';
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Econt::class, function () {
            return new Econt;
        });

        $this->app['sync'] = $this->app->singleton(Sync::class, function() {
            return new Sync;
        });

        $this->commands(Sync::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Econt'];
    }
}
