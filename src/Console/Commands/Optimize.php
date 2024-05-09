<?php

namespace App\Console\Commands;

use Dominservice\CLaravelConfig\Config;
use Illuminate\Console\Command;

class Optimize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dso:optimize-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache config ang routes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        (new Config())->buildCache();
        \Illuminate\Support\Facades\Artisan::call('route:cache');
    }
}
