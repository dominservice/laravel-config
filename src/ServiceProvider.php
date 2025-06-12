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
        $envPath = base_path('.env');
        $outputPath = base_path('optimize_config.php');

        if (!file_exists($outputPath) && file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES);
            $output = "<?php\n\nreturn [\n";

            foreach ($lines as $line) {
                $trimmed = trim($line);

                // Pusta linia
                if ($trimmed === '') {
                    $output .= "\n";
                    continue;
                }

                // Zakomentowana linia z przypisaniem
                if (preg_match('/^\s*#\s*([\w.]+)\s*=\s*(.*)$/', $line, $matches)) {
                    $key = $matches[1];
                    $value = trim($matches[2]);
                    $value = trim($value, '\'"'); // usuń nadmiarowe cudzysłowy
                    $quote = str_contains($value, '${') ? '"' : "'";
                    $output .= "    // '{$key}' => {$quote}{$value}{$quote},\n";
                    continue;
                }

                // Zwykły komentarz
                if (preg_match('/^\s*#(.*)/', $line, $matches)) {
                    $comment = trim($matches[1]);
                    $output .= "    // {$comment}\n";
                    continue;
                }

                // Normalna linia KEY=VALUE
                if (preg_match('/^\s*([\w.]+)\s*=\s*(.*)$/', $line, $matches)) {
                    $key = $matches[1];
                    $value = trim($matches[2]);
                    $value = trim($value, '\'"'); // usuń otaczające " lub '

                    $quote = str_contains($value, '${') ? '"' : "'";
//                    $output .= "    '{$key}' => {$quote}{$value}{$quote},\n";
                    $output .= "    '{$key}' => '{$value}',\n";
                    continue;
                }

                // Niezidentyfikowana linia – jako komentarz awaryjny
                $output .= "    // {$line}\n";
            }

            $output .= "];\n";

            if (file_put_contents($outputPath, $output) !== false) {
                @unlink($envPath);
            }
        }
    }
}
