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
    
    Dominservice\CLaravelConfig\ServiceProvider::class,
],

```


You should publish the migration and the config/optimize.php config file with:

```shell
php artisan vendor:publish --provider="Dominservice\CLaravelConfig\ServiceProvider"
```


## Usage

From __SHELL__

```shell
php artisan dso:optimize-config
```

From __PHP__

```php
use Dominservice\CLaravelConfig\Config;

// ...

(new Config())->buildCache();
```