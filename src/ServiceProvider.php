<?php

namespace Dominservice\CLaravelConfig;


use Dominservice\CLaravelConfig\Console\Commands\Optimize;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    private int $lpMigration = 0;

    /**
    * Bootstrap the application services.
    *
    * @return void
    */
    public function boot(Router $router, Filesystem $filesystem)
    {
        /** Migrations */
        $this->publishes([
            __DIR__.'/../database/migrations/create_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_settings_table'),

        ], 'config-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Optimize::class,
            ]);
        }
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @param $name
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem, $name): string
    {
        $this->lpMigration++;
        $timestamp = date('Y_m_d_Hi'.str_pad($this->lpMigration, 2, "0", STR_PAD_LEFT));

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $name) {
                return $filesystem->glob($path.'*'.$name.'.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_{$name}.php")
            ->first();
    }
}