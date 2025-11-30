---
title: "Write your own"
description: "Learn how to write your own Module."
sidebar:
  order: 1
---

## Module Structure

Module is a **[Service Providers](https://laravel.com/docs/providers)** that implements the [`UniGale\Foundation\Contracts\Module`](https://github.com/unigale/framework/blob/main/packages/Foundation/Contracts/Module.php)  interface.

### Required Methods

```php
public function identifier(): string;
public function identity(): ModuleIdentity;
```

* `identifier()` returns a unique module string.
* `identity()` returns metadata including name, version, author, and optionally a description.

### Example

```php
use UniGale\Foundation\Contracts\Module;
use UniGale\Foundation\ModuleIdentity;
use Illuminate\Support\ServiceProvider;

class AdministrationModule extends ServiceProvider implements Module
{
    public function identifier(): string
    {
        return 'unique-module-identifier';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Your custom module'),
            version: 'dev-master',
            author: 'Your Team',
            description: '' // Optional
        );
    }
}
```

## Related Laravel Documentation

* [Package Development](https://laravel.com/docs/packages) — Creating packages with configuration, migrations, and assets.












