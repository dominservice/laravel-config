<?php

namespace Dominservice\LaravelConfig;

use Dominservice\LaravelConfig\Helpers\ArrayHelper;
use Dominservice\LaravelConfig\Models\Setting;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
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
                $this->setToDB($v[0], $v[1]);
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

            \DB::transaction(function () use ($param, $value, $configDefault, &$config) {
                $typeVal = ArrayHelper::valueTypeOf($value);
                $castValue = ArrayHelper::valueCastTo($value, $typeVal, false);
                $defaultCastValue = ArrayHelper::valueCastTo(data_get($configDefault, $param), $typeVal, false);

                if ($defaultCastValue !== $castValue) {
                    \DB::table('settings')->upsert(['key' => $param, 'value' => $castValue], ['key'], ['value']);
                    data_set($config, $param, $value);
                } else {
                    Setting::where('key', $param)->delete();
                    Arr::forget($config, $param);
                }
            });
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
        $app = require app()->bootstrapPath('app.php');
        $app->useStoragePath(app()->storagePath());
        $app->make(ConsoleKernelContract::class)->bootstrap();

        return $app['config']->all();
    }

    protected function getFreshConfigurationForSet(): array
    {
        $filesystem = new Filesystem;
        $config = $this->getFreshConfiguration();

        // Ręczne ładowanie plików konfiguracyjnych z pakietów
        $packageManifest = app(PackageManifest::class);
        $packages = $packageManifest->manifest;

        foreach ($packages as $package) {
            if (isset($package['providers'])) {
                foreach ($package['providers'] as $provider) {
                    $reflection = new \ReflectionClass($provider);
                    $packagePath = dirname($reflection->getFileName(), 2); // Zakładamy, że katalog pakietu jest dwa poziomy wyżej
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
     * @return void
     */
    private function initConfigDB($force = false): void
    {
        if (!$this->initializedDB || $force) {
            try {
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
            require $configPath;
        } catch (Throwable $e) {
            $filesystem->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }
    }

    /**
     * @return bool
     */
    public function isBuildedCache(): bool
    {
        return app()->configurationIsCached();
    }
}
