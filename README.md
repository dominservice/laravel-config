# Laravel Config Optimize

[![Packagist](https://img.shields.io/packagist/v/dominservice/laravel-config.svg)]()
[![Latest Version](https://img.shields.io/github/release/dominservice/laravel-config.svg?style=flat-square)](https://github.com/dominservice/laravel-config/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/dominservice/laravel-config.svg?style=flat-square)](https://packagist.org/packages/dominservice/laravel-config)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Laravel Config Optimize for laravel 10+ on PHP 8.1+

## Installation

```shell
composer require dominservice/laravel-config
```

Add the service provider to config/app.php

```php
'providers' => [
    // ...
    
    Dominservice\LaravelConfig\ServiceProvider::class,
],

```


You should publish the migration and the config/optimize.php config file with:

```shell
php artisan vendor:publish --provider="Dominservice\LaravelConfig\ServiceProvider"
```


## Usage

From __SHELL__

```shell
php artisan dso:optimize-config
```

From __PHP__

```php
use Dominservice\LaravelConfig\Config;

// ...

(new Config())->buildCache();
```

Or set __Middleware__ to your __application__
```php
Dominservice\LaravelConfig\Http\Middleware\Optimize::class
```

## Issues related to using .env

* Global scope of $_ENV: Variables in $_ENV are global for a given PHP process. If the server configuration (e.g., PHP-FPM) does not properly isolate individual applications, it is possible for one project to see variables set by another.

* Lack of process isolation: In some shared hosting configurations, process pools may be shared among different applications, which increases the risk of variable mixing.

### Solution

Implement our __optimize_config()__ function instead of __env()__ in all project configuration files.

Example:

```php
config/app.php
...
     'env' => optimize_config('APP_ENV', 'production'),
...
```

To get rid of the __.env__ file, you need to add the __optimize_config.php__ file in the main project directory, where you need to enter the entire configuration from the __.env__ file, but in the form of an __array__