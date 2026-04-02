<?php

namespace Dominservice\LaravelConfig\Tests;

use Illuminate\Support\Arr;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_default_config_and_recursive_merge_macro(): void
    {
        $this->assertSame([], config('optimize.custom_files_config'));

        $merged = Arr::recursiveMerge(
            ['first' => ['enabled' => false, 'count' => 1]],
            ['first' => ['enabled' => true], 'second' => 'value']
        );

        $this->assertSame(
            ['first' => ['enabled' => true, 'count' => 1], 'second' => 'value'],
            $merged
        );
    }
}
