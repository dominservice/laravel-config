# Laravel Config Optimize

[![Packagist](https://img.shields.io/packagist/v/dominservice/laravel-config.svg)]()
[![Latest Version](https://img.shields.io/github/release/dominservice/laravel-config.svg?style=flat-square)](https://github.com/dominservice/laravel-config/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/dominservice/laravel-config.svg?style=flat-square)](https://packagist.org/packages/dominservice/laravel-config)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Merge default config with dynamic DB settings, include custom config files, and cache the result.
Laravel 10-13 on PHP 8.1+.

## Features

- DB-backed config overrides without changing your config file structure.
- Config cache builder that merges defaults + custom files + DB settings.
- Custom config file paths via `config/optimize.php` (module-like layouts).
- `optimize_config()` helper for `.env` replacement and `${VAR}` interpolation.
- Auto-generate `optimize_config.php` from `.env` on `vendor:publish`.
- CLI command and middleware for on-demand config caching.
- Route cache integration (command always, middleware in production).

## Installation

```shell
composer require dominservice/laravel-config
```

Current compatibility targets:

- Laravel 10 on PHP 8.1+
- Laravel 11 on PHP 8.2+
- Laravel 12 on PHP 8.2+
- Laravel 13 on PHP 8.3+

Laravel package discovery is enabled. Manual registration is only needed if you disabled discovery:

```php
'providers' => [
    // ...
    Dominservice\LaravelConfig\ServiceProvider::class,
],
```

Publish assets (config + migration):

```shell
php artisan vendor:publish --provider="Dominservice\\LaravelConfig\\ServiceProvider" --tag=optimize-config
php artisan vendor:publish --provider="Dominservice\\LaravelConfig\\ServiceProvider" --tag=optimize-migrations
```

Run migrations:

```shell
php artisan migrate
```

## Usage

From shell:

```shell
php artisan dso:optimize-config
```

From PHP:

```php
use Dominservice\LaravelConfig\Config;

(new Config())->buildCache();
```

### Database-backed config values

The `settings` table stores only the values that differ from defaults. Types are auto-cast based on the
default config value. If a value equals the default, the DB record is removed.

```php
use Dominservice\LaravelConfig\Config;

(new Config())->set('app.name', 'My App', true);

(new Config())->set([
    'app.debug' => false,
    'app.timezone' => 'UTC',
], true);

(new Config())->set([
    ['app.locale', 'en'],
    ['app.faker_locale', 'en_US'],
], true);
```

### Middleware (build cache on first request)

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        Dominservice\LaravelConfig\Http\Middleware\Optimize::class,
    ],
];
```

The middleware runs only for GET requests, skips JSON and Livewire requests, and redirects once after
building the cache. In production it also runs `route:cache`.

## Custom config files

Use `config/optimize.php` to merge non-standard config files (for module systems, etc).
Each key becomes the config namespace; values can be a string path or an array of paths.

```php
// config/optimize.php
return [
    'custom_files_config' => [
        'module' => 'modules/module/config/module.php',
        'payments' => [
            'modules/payments/config/gateways.php',
            'modules/payments/config/rules.php',
        ],
    ],
];
```

The package merges these arrays recursively using `Arr::recursiveMerge`.

## optimize_config helper (replace env())

`optimize_config()` reads from `optimize_config.php` in the project root and falls back to `Env::get`.
It supports `${VAR}` interpolation inside values.

Example in `config/app.php`:

```php
'env' => optimize_config('APP_ENV', 'production'),
```

### Auto-generate optimize_config.php

When you run `vendor:publish`, the service provider will:

- Create `optimize_config.php` if it does not exist and `.env` exists.
- Copy `.env` to `.env.backup`.
- Remove the original `.env`.

Review this behavior before running `vendor:publish` in production.

---

## Support
### Support this project (Ko-fi)
If this package saves you time, consider buying me a coffee: https://ko-fi.com/dominservice - thank you!

---

## License

MIT (c) Dominservice
