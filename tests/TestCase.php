<?php

namespace Dominservice\LaravelConfig\Tests;

use Dominservice\LaravelConfig\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @var array<int, string>
     */
    protected array $createdPaths = [];

    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('optimize.custom_files_config', []);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('optimize.custom_files_config', []);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->createdPaths) as $path) {
            if (is_file($path)) {
                @unlink($path);
                continue;
            }

            if (is_dir($path)) {
                @rmdir($path);
            }
        }

        $cachedConfigPath = app()->getCachedConfigPath();
        if (is_file($cachedConfigPath)) {
            @unlink($cachedConfigPath);
        }

        parent::tearDown();
    }

    protected function makeFile(string $relativePath, string $contents): string
    {
        $absolutePath = base_path($relativePath);
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);

            $current = $directory;
            while ($current !== base_path() && $current !== dirname($current)) {
                $this->createdPaths[] = $current;
                $current = dirname($current);
            }
        }

        file_put_contents($absolutePath, $contents);
        $this->createdPaths[] = $absolutePath;

        return $absolutePath;
    }
}
