<?php

namespace Plugins\ImageX\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ImageXServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();

        $this->loadMigrationsFrom(dirname(__DIR__, 2) . '/database/migrations');

        // Event::listen(UserCreated::class, UserCreatedListener::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->app->register(CommandServiceProvider::class);
        }
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/config/imagex.php', 'imagex'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/config/disks.php', 'fresns-imagex-filesystems'
        );

        $this->publishes([
            __DIR__ . '/../../config/imagex.php' => config_path('imagex.php'),
        ], 'config');
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $this->loadViewsFrom(dirname(__DIR__, 2) . '/resources/views', 'ImageX');


        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/plugins/imagex'),
        ], ['views', 'imagex-plugin-views']);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadTranslationsFrom(dirname(__DIR__, 2) . '/resources/lang', 'ImageX');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
