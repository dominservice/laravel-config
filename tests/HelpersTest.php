<?php

namespace Dominservice\LaravelConfig\Tests;

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
}
