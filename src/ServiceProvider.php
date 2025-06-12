<?php

namespace Dominservice\LaravelConfig;


use Dominservice\LaravelConfig\Console\Commands\Optimize;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
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
        $this->publishes([
            __DIR__ . '/../config/optimize.php' => config_path('optimize.php'),
        ], 'optimize-config');

        /** Migrations */
        $this->publishes([
            __DIR__.'/../database/migrations/create_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_settings_table'),

        ], 'optimize-migrations');


        if ($this->app->runningInConsole()) {
            if ($this->isVendorPublishing()) {
                $this->generateEnvPhpArrayFile();
            }

            $this->commands([
                Optimize::class,
            ]);
        }

        Arr::macro('recursiveMerge', function (array $array1, array $array2) {
            foreach ($array2 as $key => $value) {
                if (Arr::exists($array1, $key)) {
                    if (is_array($array1[$key]) && is_array($value)) {
                        $array1[$key] = Arr::recursiveMerge($array1[$key], $value);
                    } else {
                        $array1[$key] = $value;
                    }
                } else {
                    $array1[$key] = $value;
                }
            }

            return $array1;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/optimize.php',
            'cms'
        );
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

    protected function isVendorPublishing(): bool
    {
        $argv = $_SERVER['argv'] ?? [];

        return in_array('vendor:publish', $argv);
    }

    protected function generateEnvPhpArrayFile(): void
    {
        if (!file_exists(base_path('optimize_config.php')) && file_exists(base_path('.env'))) {
            $lines = file(base_path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $envArray = [];

            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }

                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $value = trim($value);
                    $value = trim($value, '"\'');
                    $envArray[trim($key)] = $value;
                }
            }


            $content = "<?php\n\nreturn " . var_export($envArray, true) . ";\n";
            file_put_contents(base_path('optimize_config.php'), $content);
        }
    }
}