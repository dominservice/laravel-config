<?php

namespace Dominservice\LaravelConfig\Tests;

use Illuminate\Support\Env;

class HelpersTest extends TestCase
{
    public function test_optimize_config_reads_generated_file_and_interpolates_nested_values(): void
    {
        $this->makeFile('optimize_config.php', <<<'PHP'
<?php

return [
    'APP_NAME' => 'Demo',
    'APP_URL' => 'https://${APP_NAME}.example.test',
];
PHP);

        $this->assertSame('Demo', optimize_config('APP_NAME'));
        $this->assertSame('https://Demo.example.test', optimize_config('APP_URL'));
        $this->assertSame('fallback', optimize_config('MISSING_KEY', 'fallback'));
    }

    public function test_environment_values_override_generated_config_during_tests(): void
    {
        $this->makeFile('optimize_config.php', <<<'PHP'
<?php

return [
    'TEST_OPTIMIZE_VALUE' => 'generated-value',
];
PHP);

        $environment = Env::getRepository();
        $originalValue = Env::get('TEST_OPTIMIZE_VALUE');
        $environment->set('TEST_OPTIMIZE_VALUE', 'phpunit-value');

        try {
            $this->assertSame('testing', app()->environment());
            $this->assertSame('phpunit-value', optimize_config('TEST_OPTIMIZE_VALUE'));
        } finally {
            if ($originalValue === null) {
                $environment->clear('TEST_OPTIMIZE_VALUE');
            } else {
                $environment->set('TEST_OPTIMIZE_VALUE', (string) $originalValue);
            }
        }
    }
}
