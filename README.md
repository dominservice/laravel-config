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

Publish migrations:

```shell
php artisan vendor:publish --tag=stripe-migrations
```

## Usage

From __SHELL__

```shell
php artisan dso:optimize-config
```

From __PHP__

```php
use Dominservice\CLaravelConfig\Config;

(...)

(new Config())->buildCache();
```