<?php

namespace Dominservice\LaravelConfig;

use Dominservice\LaravelConfig\Helpers\ArrayHelper;
use Dominservice\LaravelConfig\Models\Setting;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Arr;
use LogicException;
use Throwable;

class Config
{
    private $default;
    private $config = [];
    private bool $initializedDB = false;

    /**
     * @param array|string|null $param
     * @param mixed $value
     * @param bool $buildCache
     * @return $this
     */
    public function set($param = null, $value = null, bool $buildCache = false): Config
    {
        $this->initConfigDB();
        $this->getDefault();

        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (is_int($k) && is_array($v) && array_key_exists(0, $v)) {
                    // format: [ [key, value], ... ]
                    $this->setToDB($v[0], $v[1]);
                } else {
                    // format: [ 'config.key' => 'value', ... ]
                    $this->setToDB($k, $v);
                }
            }
        } else {
            $this->setToDB($param, $value);
        }

        if ($buildCache) {
            $this->buildCache();
        }

        return $this;
    }

    private function setToDB(?string $param = null, $value = null): Config
    {
        if (!is_null($param)) {
            $configDefault = $this->default;
            $config = &$this->config;
            $defaultRaw = data_get($configDefault, $param);
            $defaultType = $defaultRaw !== null
                ? ArrayHelper::valueTypeOf($defaultRaw)
                : ArrayHelper::valueTypeOf($value);

            $castValue = ArrayHelper::valueCastTo($value, $defaultType, false);
            $defaultCastValue = ArrayHelper::valueCastTo($defaultRaw, $defaultType, false);

            if ($defaultCastValue !== $castValue) {
                \DB::table('settings')->upsert(['key' => $param, 'value' => $castValue], ['key'], ['value']);
                data_set($config, $param, $value);
            } else {
                Setting::where('key', $param)->delete();
                Arr::forget($config, $param);
            }
        }

        return $this;
    }

    /**
     * @return void
     */
    private function getDefault(): void
    {
        if (!$this->default) {
            $this->default = $this->getFreshConfigurationForSet();
        }
    }

    /**
     * @return void
     */
    private function getCustomConfigFiles(): void
    {
        if ($custom = config('optimize.custom_files_config')) {
            foreach ($custom as $key => $path) {
                if (is_array($path)) {
                    foreach ($path as $item) {
                        if (file_exists(base_path($item))) {
                            $this->default[$key] = isset($this->default[$key])
                                ? Arr::recursiveMerge($this->default[$key], include(base_path($item)))
                                : include(base_path($item));
                        }
                    }
                } else {
                    if (file_exists(base_path($path))) {
                        $this->default[$key] = isset($this->default[$key])
                            ? Arr::recursiveMerge($this->default[$key], include(base_path($path)))
                            : include(base_path($path));
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getFreshConfiguration(): array
    {
        $bootstrapPath = $this->getBootstrapAppPath();

        if (! is_file($bootstrapPath)) {
            return app('config')->all();
        }

        $app = require $bootstrapPath;
        $app->useStoragePath(app()->storagePath());
        $app->make(ConsoleKernelContract::class)->bootstrap();

        return $app['config']->all();
    }

    protected function getBootstrapAppPath(): string
    {
        return app()->bootstrapPath('app.php');
    }

    protected function getFreshConfigurationForSet(): array
    {
        $filesystem = new Filesystem;
        $config = $this->getFreshConfiguration();

        // Ręczne ładowanie plików konfiguracyjnych z pakietów
        foreach ($this->getPackageProviders() as $provider) {
            try {
                $reflection = new \ReflectionClass($provider);
            } catch (Throwable) {
                continue;
            }

            $packagePath = dirname($reflection->getFileName(), 2);
            $configPath = $packagePath . '/config';

            if ($filesystem->isDirectory($configPath)) {
                foreach ($filesystem->allFiles($configPath) as $file) {
                    $filename = $file->getFilenameWithoutExtension();

                    if (isset($config[$filename])) {
                        $config[$filename] = array_merge($config[$filename], require $file->getPathname());
                    } else {
                        $config[$filename] = require $file->getPathname();
                    }
                }
            }
        }

        // Ręczne ładowanie plików konfiguracyjnych z katalogu config
        foreach ($filesystem->allFiles(config_path()) as $file) {
            $filename = $file->getFilenameWithoutExtension();

            if (isset($config[$filename])) {
                $config[$filename] = array_merge($config[$filename], require $file->getPathname());
            } else {
                $config[$filename] = require $file->getPathname();
            }
        }

        return $config;
    }

    /**
     * @return array<int, string>
     */
    protected function getPackageProviders(): array
    {
        $packageManifest = app(PackageManifest::class);

        if (method_exists($packageManifest, 'providers')) {
            return $packageManifest->providers();
        }

        return Arr::flatten(array_column($packageManifest->manifest ?? [], 'providers'));
    }

    /**
     * @return void
     */
    private function initConfigDB($force = false): void
    {
        if (!$this->initializedDB || $force) {
            try {
                if ($force) {
                    $this->config = [];
                }

                foreach (Setting::orderBy('key', 'asc')->get() as $v) {
                    $type = ArrayHelper::valueTypeOf($v->value);
                    $v->value = ArrayHelper::valueCastTo($v->value, $type);
                    data_set($this->config, $v->key, $v->value);
                }

                $this->initializedDB = true;
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function buildCache(): void
    {
        $this->initConfigDB(true);
        $this->getDefault();
        $this->getCustomConfigFiles();
        $config = Arr::recursiveMerge($this->default, $this->config);
        $configPath = app()->getCachedConfigPath();
        $filesystem = new Filesystem;
        $filesystem->delete($configPath);
        $filesystem->put(
            $configPath, '<?php return ' . var_export($config, true) . ';' . PHP_EOL
        );

        try {
            if ($filesystem->exists($configPath)) {
                $this->replaceRuntimeConfiguration(
                    $this->loadFreshCachedConfiguration($configPath, $config)
                );
            }

        } catch (Throwable $e) {
            $filesystem->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $fallback
     * @return array<string, mixed>
     */
    protected function loadFreshCachedConfiguration(string $configPath, array $fallback): array
    {
        clearstatcache(true, $configPath);

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($configPath, true);
        }

        $loaded = require $configPath;

        return is_array($loaded) ? $loaded : $fallback;
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function replaceRuntimeConfiguration(array $config): void
    {
        app()->forgetInstance('config');
        Facade::clearResolvedInstance('config');

        app()->instance('config', new ConfigRepository($config));
    }

    /**
     * @return bool
     */
    public function isBuildedCache(): bool
    {
        return app()->configurationIsCached();
    }
}
