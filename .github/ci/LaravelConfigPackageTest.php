<?php

namespace Tests\Feature;

use Dominservice\LaravelConfig\Config;
use Dominservice\LaravelConfig\Models\Setting;
use Tests\TestCase;

class LaravelConfigPackageTest extends TestCase
{
    public function test_package_builds_cached_configuration_with_database_and_custom_file_overrides(): void
    {
        file_put_contents(config_path('sample.php'), <<<'PHP'
<?php

return [
    'enabled' => false,
    'name' => 'default-name',
    'nested' => [
        'count' => 1,
    ],
];
PHP);

        if (! is_dir(base_path('modules/example/config'))) {
            mkdir(base_path('modules/example/config'), 0777, true);
        }

        file_put_contents(base_path('modules/example/config/module.php'), <<<'PHP'
<?php

return [
    'feature' => [
        'enabled' => true,
    ],
];
PHP);

        config()->set('optimize.custom_files_config', [
            'module' => 'modules/example/config/module.php',
        ]);

        $config = new Config();
        $config->set('sample.name', 'database-name');
        $config->set('sample.enabled', true);
        $config->buildCache();

        $cached = require app()->getCachedConfigPath();

        $this->assertSame('database-name', data_get($cached, 'sample.name'));
        $this->assertTrue(data_get($cached, 'sample.enabled'));
        $this->assertSame(1, data_get($cached, 'sample.nested.count'));
        $this->assertTrue(data_get($cached, 'module.feature.enabled'));
        $this->assertDatabaseHas('settings', [
            'key' => 'sample.name',
            'value' => 'database-name',
        ]);

        $config->set('sample.name', 'default-name');

        $this->assertDatabaseMissing('settings', [
            'key' => 'sample.name',
        ]);
    }
}
