---
title: Installation
description: Learn how to install and bootstrap Unigale in your Laravel application.
slug: "getting-started/installation"
---

**Unigale** is a modular application framework built on top of Laravel.
It provides a robust architecture for building extensible systems composed of isolated modules, shared integrations, and
configurable execution pipelines.

Unigale enhances Laravel's foundation without altering its philosophy, offering additional capabilities such as module
lifecycle management, multi-core orchestration, workflow execution, and centralized configuration.


## Requirements

Unigale follows the same requirements as your Laravel version.
There are no additional PHP extension requirements.

Ensure that your environment meets:

- PHP version required by your Laravel installation
- Composer
- A working Laravel application, supported version are:
    - Laravel 12.x



## 1. Install the Package

Install Unigale via Composer:

```bash
composer require unigale/framework
````

## 2. Update `bootstrap/app.php`

Unigale replaces Laravel’s base application class with its own `UnigaleApplication`.

Edit your `bootstrap/app.php`:

```php
use UniGale\Foundation\UnigaleApplication;

return UnigaleApplication::configure(basePath: dirname(__DIR__))
    ->withRouting()
    ->withMiddleware()
    ->withExceptions()
    ->create();
```

## 2. Re-run migrations

Unigale injects its own database migrations during the bootstrapping process.
After installing the package, run the migrations to ensure all required tables are created:

```bash
php artisan migrate
```
This will apply both Laravel’s existing migrations and the additional Unigale migrations.
