<?php

namespace Dominservice\LaravelConfig\Tests;

use Dominservice\LaravelConfig\Config;
use Illuminate\Foundation\PackageManifest;
use Mockery;

class ConfigCompatibilityTest extends TestCase
{
    public function test_it_falls_back_to_current_application_config_when_bootstrap_file_is_missing(): void
    {
        config()->set('app.name', 'Fallback App');

        $config = new class extends Config
        {
            protected function getBootstrapAppPath(): string
            {
                return base_path('bootstrap/non-existent-app.php');
            }

            public function freshConfiguration(): array
            {
                return $this->getFreshConfiguration();
            }
        };

        $fresh = $config->freshConfiguration();

        $this->assertSame('Fallback App', data_get($fresh, 'app.name'));
    }

    public function test_it_uses_package_manifest_providers_api(): void
    {
        $manifest = Mockery::mock(PackageManifest::class);
        $manifest->shouldReceive('providers')
            ->once()
            ->andReturn([
                'Illuminate\\Auth\\AuthServiceProvider',
                'Dominservice\\LaravelConfig\\ServiceProvider',
            ]);

        $this->app->instance(PackageManifest::class, $manifest);

        $config = new class extends Config
        {
            public function packageProviders(): array
            {
                return $this->getPackageProviders();
            }
        };

        $this->assertSame([
            'Illuminate\\Auth\\AuthServiceProvider',
            'Dominservice\\LaravelConfig\\ServiceProvider',
        ], $config->packageProviders());
    }
}
