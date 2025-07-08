<?php

namespace Dominservice\LaravelConfig\Http\Middleware;

use Closure;
use Dominservice\LaravelConfig\Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Optimize
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->configurationIsCached()
            && !app()->runningInConsole()
            && !app()->runningUnitTests()
            && !$request->header('X-Livewire')
            && !$request->expectsJson()
            && !str_starts_with($request->path(), 'livewire')
            && $request->isMethod('GET')
        ) {
            (new Config())->buildCache();

            if (app()->isProduction()) {
                \Illuminate\Support\Facades\Artisan::call('route:cache');
            }

            return redirect($request->url());
        }

        return $next($request);
    }
}