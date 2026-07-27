# SchoolPalm App Settings

<p align="center">
    <a href="https://packagist.org/packages/schoolpalm/app-settings"><img src="https://img.shields.io/packagist/v/schoolpalm/app-settings.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/schoolpalm/app-settings/actions"><img src="https://img.shields.io/github/actions/workflow/status/schoolpalm/app-settings/run-tests.yml?branch=main&style=flat-square&label=tests" alt="GitHub Tests"></a>
    <a href="https://packagist.org/packages/schoolpalm/app-settings"><img src="https://img.shields.io/packagist/dt/schoolpalm/app-settings.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/schoolpalm/app-settings"><img src="https://img.shields.io/packagist/l/schoolpalm/app-settings.svg?style=flat-square" alt="License"></a>
    <a href="https://php.net"><img src="https://img.shields.io/packagist/php-v/schoolpalm/app-settings.svg?style=flat-square" alt="PHP Version"></a>
</p>

A lightweight settings abstraction package that provides a consistent API for reading and writing application settings without exposing how or where those settings are stored.

The package does **not** know anything about:

- Database
- Eloquent Models
- Tenants
- Schools
- Caching
- Redis
- Laravel Cache
- Any storage engine

Instead, the package delegates all persistence responsibilities to an application provided **SettingsService**.

* * *

## Installation

You can install the package via Composer:

```bash
composer require schoolpalm/app-settings
```

### Laravel Auto-Discovery

The package's service provider and facade are automatically discovered by Laravel. No manual registration is required.

If you are using Laravel, the `AppSettings` facade will be available automatically.

### Publishing Configuration

To publish the configuration file:

```bash
php artisan vendor:publish --tag=app-settings-config
```

### Suggested Package

For high-performance cached settings with multi-tenant and multi-school support, we recommend installing:

```bash
composer require schoolpalm/cache-store
```

This allows your `SettingsService` implementation to leverage context-aware caching while keeping the **app-settings** package completely decoupled.

* * *

## Goals

- Simple developer API
- Database independent
- Tenant independent
- School independent
- Storage independent
- Cache independent
- Easy to test
- Works with any backend implementation

* * *

## High Level Architecture

```
               Application

                    │

                    ▼

            AppSettings Facade

                    │

                    ▼

            AppSettings Manager

                    │

                    ▼

            SettingsService Contract

                    │

          Application Implementation

        ┌──────────────┴───────────────┐

        ▼                              ▼

   Cache Store                  Database / API
```

**Important**

The package only communicates with **SettingsService**. How the service retrieves data is entirely the responsibility of the consuming application.

* * *

## Folder Structure

```
app-settings/

├── src/
│
├── Contracts/
│   └── SettingsService.php
│
├── Facades/
│   └── AppSettings.php
│
├── Managers/
│   └── SettingsManager.php
│
├── Exceptions/
│   └── SettingsException.php
│
├── Providers/
│   └── AppSettingsServiceProvider.php
│
└── config/
    └── app-settings.php
```

* * *

## Core Components

| Component       | Responsibility                        |
| --------------- | ------------------------------------- |
| Facade          | Developer friendly API                |
| Manager         | Delegates requests to SettingsService |
| SettingsService | Storage abstraction                   |
| Application     | Implements database and caching       |

* * *

## Settings Service Contract

```

interface SettingsService
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    public function all(): array;

    public function flush(): void;
}
```

* * *

## Developer API

The package intentionally exposes a minimal and expressive API.

```

use SchoolPalm\AppSettings\Facades\AppSettings;
```

### Store a Setting

```

AppSettings::put(
    'school.name',
    'Emma High School'
);
```

### Retrieve a Setting

```

$name = AppSettings::get('school.name');
```

### Retrieve with Default Value

```

$name = AppSettings::get(
    'school.name',
    'Unknown School'
);
```

### Determine if a Setting Exists

```

AppSettings::has('school.logo');
```

### Delete a Setting

```

AppSettings::forget('school.logo');
```

### Retrieve All Settings

```

$settings = AppSettings::all();
```

### Remove All Settings

```

AppSettings::flush();
```

* * *

## Fluent API

To keep consistency with other SchoolPalm infrastructure packages, the manager can expose a fluent API.

### Example

```

AppSettings::put('school.name', 'Emma High')
    ->put('school.email', 'info@emma.sc.ug')
    ->put('school.phone', '+256700000000');
```

```

AppSettings::forget('school.logo')
    ->forget('school.banner');
```

### Reading Fluently

```

$value = AppSettings
            ->key('school.name')
            ->default('Unknown')
            ->get();
```

Equivalent to:

```

$value = AppSettings::get(
    'school.name',
    'Unknown'
);
```

* * *

## Possible Fluent Builder

```

AppSettings::key('school.name')
    ->value('Emma High School')
    ->save();
```

```

AppSettings::key('school.logo')
    ->delete();
```

```

AppSettings::key('school.name')
    ->exists();
```

```

AppSettings::key('school.theme')
    ->default('blue')
    ->get();
```

This builder is optional but provides a consistent fluent experience across the SchoolPalm package ecosystem.

* * *

## Supported Value Types

| Type    | Supported        |
| ------- | ---------------- |
| string  | ✔                |
| integer | ✔                |
| float   | ✔                |
| boolean | ✔                |
| array   | ✔                |
| object  | ✔ (serializable) |
| null    | ✔                |

* * *

## Example Application Implementation

Inside SchoolPalm, the application may implement the contract as follows:

```

class SchoolSettingsService
    implements SettingsService
{
    // Uses CacheStore

    // Uses Eloquent

    // Uses Tenant Context

    // Uses School Context

    // Package never sees any of this.
}
```

* * *

## Responsibilities

| App Settings Package | Application            |
| -------------------- | ---------------------- |
| Developer API        | Database               |
| Facade               | Eloquent Models        |
| Manager              | Cache Store            |
| Contracts            | Tenant Resolution      |
| Exceptions           | School Resolution      |
| Validation           | Serialization Strategy |
| Fluent Builder       | Cache Keys             |

* * *

## Integration with Cache Store

The **app-settings** package never communicates directly with **cache-store**. Instead:

```
AppSettings

      │

      ▼

SettingsService

      │

      ▼

CacheStore

      │

      ▼

Database
```

This keeps the package completely decoupled from any caching implementation while still allowing consuming applications to leverage **schoolpalm/cache-store** for high-performance setting retrieval.

* * *

## Future Extensions

- Typed settings
- Setting validation rules
- Encrypted settings
- Read-only settings
- Namespaced settings
- Bulk updates
- Import / Export
- Event dispatching
- Setting metadata
- Setting groups
- Setting observers
- Settings versioning

* * *

## Design Philosophy

**app-settings** is an abstraction layer, not a storage engine.

Its only responsibility is to provide a clean, expressive, and fluent API for working with application settings while delegating persistence to an application provided **SettingsService**.

This architecture ensures that the package remains reusable, testable, and agnostic of databases, tenants, schools, caching mechanisms, or any specific application infrastructure.
