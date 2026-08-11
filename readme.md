# SchoolPalm App Settings

[![Latest Version on Packagist](https://img.shields.io/packagist/v/schoolpalm/app-settings.svg?style=flat-square)](https://packagist.org/packages/schoolpalm/app-settings) [![GitHub Tests](https://img.shields.io/github/actions/workflow/status/schoolpalm/app-settings/run-tests.yml?branch=main&style=flat-square&label=tests)](https://github.com/schoolpalm/app-settings/actions) [![Total Downloads](https://img.shields.io/packagist/dt/schoolpalm/app-settings.svg?style=flat-square)](https://packagist.org/packages/schoolpalm/app-settings) [![License](https://img.shields.io/packagist/l/schoolpalm/app-settings.svg?style=flat-square)](https://packagist.org/packages/schoolpalm/app-settings) [![PHP Version](https://img.shields.io/packagist/php-v/schoolpalm/app-settings.svg?style=flat-square)](https://php.net)

A lightweight, storage-agnostic settings abstraction package for Laravel that provides a consistent, expressive API for reading and writing application settings — without exposing how or where those settings are stored.

**The package does not know anything about:**

- Database engines
- Eloquent Models
- Tenants or Schools
- Caching mechanisms (Redis, Laravel Cache, etc.)
- Any specific storage engine

Instead, the package delegates all persistence responsibilities to a layered architecture: **Facade → Manager → Builder → Resolver → Repository**.

* * *

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Architecture](#architecture)
- [Quick Start](#quick-start)
- [Basic CRUD Operations](#basic-crud-operations)
- [Dot-Notation Path Shorthand (settings/setting)](#dot-notation-shorthand)
- [Fluent Builder API](#fluent-builder-api)
- [Settings Scope](#settings-scope)
- [Context Isolation](#context-isolation)
- [Settings Groups](#settings-groups)
- [Database Connections](#database-connections)
- [Cache Integration](#cache-integration)
- [Bulk Operations](#bulk-operations)
- [Setting Value Types](#setting-value-types)
- [Exception Handling](#exception-handling)
- [Migrations](#migrations)
- [Testing](#testing)
- [Complete API Reference](#complete-api-reference)
- [Design Philosophy](#design-philosophy)

* * *

## Installation

### Install via Composer

```
composer require schoolpalm/app-settings
```

### Laravel Auto-Discovery

The package's service provider and facade are automatically discovered by Laravel. No manual registration is required.

- **Service Provider:** `SchoolPalm\AppSettings\Providers\AppSettingsServiceProvider`
- **Facade:** `SchoolPalm\AppSettings\Facades\AppSettings`

The `AppSettings` facade is available globally after installation.

### Publishing Configuration

```
php artisan vendor:publish --tag=app-settings-config
```

This publishes the configuration file to `config/app-settings.php`.

### Publishing Migrations

```
# Publish main migration (standard applications)
php artisan vendor:publish --tag=app-settings-migrations

# Publish tenant migration (multi-tenant applications)
php artisan vendor:publish --tag=app-settings-tenant-migrations
```

Run migrations after publishing:

```
php artisan migrate
```

### Suggested Package

For high-performance cached settings with multi-tenant and multi-school support:

```
composer require schoolpalm/cache-store
```

This enables cache-first resolution while keeping the **app-settings** package completely decoupled from caching logic.

* * *

## Configuration

Publish the configuration file and customize it:

```
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection used when no connection is explicitly provided.
    | This allows the package to work in normal Laravel applications without
    | requiring multi-tenancy.
    |
    | Examples: 'mysql', 'sqlite', 'tenant'
    |
    | In SchoolPalm this can normally remain null because the tenant
    | connection is provided dynamically.
    */

    'connection' => env('APP_SETTINGS_CONNECTION', config('database.default')),


    /*
    |--------------------------------------------------------------------------
    | Default Context
    |--------------------------------------------------------------------------
    |
    | Optional default context applied when no context is explicitly provided.
    |
    | Example: ['type' => 'school', 'id' => 1]
    */

    'context' => null,


    /*
    |--------------------------------------------------------------------------
    | Default Group
    |--------------------------------------------------------------------------
    |
    | Optional default settings group.
    */

    'group' => null,


    /*
    |--------------------------------------------------------------------------
    | Settings Key Separator
    |--------------------------------------------------------------------------
    |
    | Character used to separate key segments.
    */

    'key_separator' => env('APP_SETTINGS_KEY_SEPARATOR', '.'),


    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache implementation is provided by cache-store.
    */

    'cache' => [
        'enabled' => env('APP_SETTINGS_CACHE', true),
        'ttl'     => env('APP_SETTINGS_CACHE_TTL', 3600),
    ],


    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Global default values for settings keys.
    */

    'defaults' => [],


    /*
    |--------------------------------------------------------------------------
    | Migration Mode
    |--------------------------------------------------------------------------
    |
    | Controls the migration target:
    |
    | - 'central': Standard Laravel application.
    | - 'tenant': Multi-tenant applications.
    */

    'migration' => [
        'mode' => env('APP_SETTINGS_MIGRATION_MODE', 'central'),
    ],
];
```

* * *

## Architecture

### High-Level Architecture

```
                Application
                     │
                     ▼
            AppSettings Facade
                     │
                     ▼
              SettingsManager
                     │
                     ▼
              SettingsBuilder
                     │
                     ▼
             SettingsResolver
                     │
             ┌───────┴───────┐
             ▼               ▼
    SettingsRepository    CacheStore
             │               │
             ▼               ▼
          Database        Redis / Cache
```

### Layer Responsibilities

| Layer                  | Responsibility                                       |
| ---------------------- | ---------------------------------------------------- |
| **Facade**             | Developer-friendly static API (`AppSettings::get()`) |
| **SettingsManager**    | Entry point, delegates to Builder                    |
| **SettingsBuilder**    | Fluent API, manages `SettingsScope`                  |
| **SettingsResolver**   | Cache-first resolution strategy                      |
| **SettingsRepository** | Database persistence via Eloquent                    |
| **SettingsScope**      | Value object for operation context                   |

### Key Design Principle

The package only communicates with the **database** through `SettingsRepository`. It never directly knows about:

- Which database engine is used
- How caching is implemented
- What tenant/school resolution strategy the application uses
- How cache keys are generated

This keeps the package reusable, testable, and completely decoupled from application infrastructure.

* * *

## Quick Start

```
use SchoolPalm\AppSettings\Facades\AppSettings;

// Store a setting
AppSettings::put('school.name', 'Emma High School');

// Retrieve a setting
$name = AppSettings::get('school.name');
// Result: 'Emma High School'

// Retrieve directly via nested dot-notation path shorthand
$enabled = AppSettings::settings('message_delivery.email.laravel-mail.enabled');

// Retrieve with a default value
$motto = AppSettings::get('school.motto', 'Excellence');
// Result: 'Excellence' (since 'school.motto' doesn't exist)

// Check if a setting exists
if (AppSettings::has('school.name')) {
    // Setting exists
}

// Delete a setting
AppSettings::forget('school.name');

// Delete all settings (DB + cache) in the current scope
AppSettings::flush();

// Get all settings
$allSettings = AppSettings::all();
```

* * *

## Basic CRUD Operations

### Store a Setting (`put`)

```
AppSettings::put('school.name', 'Emma High School');
AppSettings::put('school.enrollment', 1200);
AppSettings::put('school.is_active', true);
AppSettings::put('school.subjects', ['Math', 'Science', 'English']);
```

The `put()` method returns `$this` for chaining:

```
AppSettings::put('school.name', 'Emma High')
    ->put('school.email', 'info@emma.sc.ug')
    ->put('school.phone', '+256700000000');
```

### Retrieve a Setting (`get`)

```
// Simple retrieval
$name = AppSettings::get('school.name');

// With default value
$motto = AppSettings::get('school.motto', 'Default Motto');

// Type-cast values are preserved
$enrollment = AppSettings::get('school.enrollment'); // (int) 1200
$isActive   = AppSettings::get('school.is_active');  // (bool) true
```

### Check if Setting Exists (`has`)

```
if (AppSettings::has('school.name')) {
    // The setting exists in storage
}
```

> **Note:** `has()` checks storage only. It does not consider default values.

### Delete a Setting (`forget`)

```
AppSettings::forget('school.logo');
```

Returns `$this` for chaining:

```
AppSettings::forget('school.logo')
    ->forget('school.banner');
```

### Retrieve All Settings (`all`)

```
$settings = AppSettings::all();
// Returns: ['school.name' => 'Emma High', 'school.enrollment' => 1200, ...]
```

### Delete All Settings (`flush`)

`flush()` removes **all settings in the current scope** from **both the database and the cache**, preventing stale cached values from bleeding into subsequent reads.

```
// Clears ALL global settings (DB + cache)
AppSettings::flush();

// Clears all settings for a context (DB + cache)
AppSettings::context('school', 1)->flush();

// Clears all settings for a group within a context (DB + cache)
AppSettings::context('school', 1)
    ->group('grading')
    ->flush();
```

`flush()` is scope-aware:

- `AppSettings::flush()` — clears the global (context-less) scope
- `AppSettings::context('school', 1)->flush()` — clears **only** school 1; other schools are untouched
- `AppSettings::context('school', 1)->group('grading')->flush()` — clears **only** the `grading` group of school 1; other groups are untouched

* * *

## Dot-Notation Path Shorthand (settings / setting)

Instead of explicitly setting group scope and executing a parameterless `get()`, both `SettingsAdapter` and `SettingsBuilder` provide direct shorthand reading methods: `settings()` and its alias `setting()`.

### Traditional Group Retrieval

```
$enabled = AppSettings::group('message_delivery.email.laravel-mail.enabled')->get();
```

### Shorthand Retrieval

```
// Using settings()
$enabled = AppSettings::settings('message_delivery.email.laravel-mail.enabled');

// Using singular setting() alias with fallback default
$enabled = AppSettings::setting('message_delivery.email.laravel-mail.enabled', true);
```

### Method Signatures

```
public function settings(?string $path = null, mixed $default = null): mixed;
public function setting(?string $path = null, mixed $default = null): mixed;
```

* * *

## Fluent Builder API

The package provides a fluent builder for more complex operations. The builder methods can be chained to build up a `SettingsScope`, and then perform an operation.

### Connection → Context → Group → Action

```
AppSettings::connection('tenant')
    ->context('school', 10)
    ->group('report_cards')
    ->put('show_photo', true);

$value = AppSettings::connection('tenant')
    ->context('school', 10)
    ->group('report_cards')
    ->get('show_photo');

// Or using shorthand path reading directly off context
$value = AppSettings::connection('tenant')
    ->context('school', 10)
    ->setting('report_cards.show_photo');
```

### Using `SettingsBuilder` Directly via `withScope`

```
use SchoolPalm\AppSettings\Support\SettingsScope;

$scope = new SettingsScope(
    connection: 'tenant',
    contextType: 'school',
    contextId: 10,
    group: 'report_cards'
);

$builder = AppSettings::withScope($scope);
$value = $builder->get('show_photo');
```

### Method Chaining on Manager

The `SettingsManager` itself returns `$this` from mutating methods:

```
AppSettings::put('theme.color', 'blue')
    ->put('theme.font', 'Arial')
    ->put('theme.size', 14);

AppSettings::forget('theme.color')
    ->forget('theme.font');
```

* * *

## Settings Scope

`SettingsScope` is a **value object** that defines the isolation boundaries for a setting operation. It is immutable — each modifier method returns a new instance.

### Scope Properties

| Property       | Type    | Description |
| -------------- | ------- | ----------- |
| `connection`   | `string | null`       | Database connection name                     |
| `contextType`  | `string | null`       | Context namespace (e.g., 'school', 'branch') |
| `contextId`    | `string | int         | null`                                        | Context identifier (e.g., school ID) |
| `group`        | `string | null`       | Settings group (e.g., 'report\_cards')       |
| `cacheContext` | `mixed  | null`       | Cache isolation context                      |

### Creating a Scope

```
use SchoolPalm\AppSettings\Support\SettingsScope;

// Global scope (no isolation)
$global = new SettingsScope();

// School-specific scope
$school = new SettingsScope(
    contextType: 'school',
    contextId: 15
);

// Fully scoped
$scoped = new SettingsScope(
    connection: 'tenant',
    contextType: 'school',
    contextId: 15,
    group: 'report_cards'
);
```

### Immutable Scope Modification

Each modifier returns a **new** instance:

```
$scope = new SettingsScope();

$tenantScope = $scope->withConnection('tenant');
$schoolScope = $tenantScope->withContext('school', 10);
$groupScope  = $schoolScope->withGroup('report_cards');
$cachedScope = $groupScope->withCacheContext(['tenant_abc', 'school_123']);

// Original $scope remains unchanged
```

### Helper Methods

```
$scope->connection();     // Get connection name
$scope->contextType();    // Get context type
$scope->contextId();      // Get context ID
$scope->group();          // Get group name
$scope->hasContext();     // Whether scope has a context
$scope->hasGroup();       // Whether scope belongs to a group
$scope->toArray();        // Convert scope to array (useful for debugging)
```

* * *

## Context Isolation

Contexts allow you to isolate settings for specific entities (schools, branches, users, departments, etc.).

### Why Contexts?

Without context, all settings are **global**. Contexts enable **multi-tenant** or **multi-entity** settings where each entity has its own set of values.

### Using Contexts

```
// Store settings for different schools
AppSettings::context('school', 1)->put('name', 'School One');
AppSettings::context('school', 2)->put('name', 'School Two');

// Retrieve settings per school
$school1Name = AppSettings::context('school', 1)->get('name'); // 'School One'
$school2Name = AppSettings::context('school', 2)->get('name'); // 'School Two'
```

### How It Works

When a context is applied, the package queries the database with:

```
WHERE context_type = 'school'
  AND context_id = 10
```

Without context:

```
WHERE context_type IS NULL
  AND context_id IS NULL
```

### Database Structure

| context\_type | context\_id | group         | key         | value    |
| ------------- | ----------- | ------------- | ----------- | -------- |
| NULL          | NULL        | NULL          | app.name    | "My App" |
| school        | 1           | report\_cards | show\_photo | true     |
| school        | 2           | report\_cards | show\_photo | false    |

### Custom Context Types

You can use any context type that fits your application:

```
AppSettings::context('branch', 5)->put('currency', 'UGX');
AppSettings::context('user', 42)->put('theme', 'dark');
AppSettings::context('department', 'engineering')->put('lead', 'John');
```

* * *

## Settings Groups

Groups provide a way to organize related settings together.

### Using Groups

```
// Store grouped settings
AppSettings::group('report_cards')
    ->put('show_photo', true);

AppSettings::group('report_cards')
    ->put('show_signature', false);

// Retrieve grouped settings
$showPhoto = AppSettings::group('report_cards')
    ->get('show_photo'); // true

// Get all settings within a group
$reportCardSettings = AppSettings::group('report_cards')->all();
// Returns: ['show_photo' => true, 'show_signature' => false]
```

### Groups with Contexts

Groups and contexts compose naturally:

```
// School-specific report card settings
AppSettings::context('school', 10)
    ->group('report_cards')
    ->put('show_photo', true);

// School-specific grading settings
AppSettings::context('school', 10)
    ->group('grading')
    ->put('pass_mark', 50);
```

### Common Group Use Cases

- `report_cards` — Report card display settings
- `grading` — Grade calculation rules
- `sms` — SMS notification settings
- `payroll` — Payroll configuration
- `theme` — Application theming
- `notifications` — Notification preferences

* * *

## Database Connections

The package supports dynamic database connections, enabling **tenant isolation** at the database level.

### Using Connections

```
// Store on the default connection
AppSettings::put('app.name', 'My App');

// Store on a specific tenant connection
AppSettings::connection('tenant_abc')
    ->put('school.name', 'Emma High');

// Retrieve from a specific connection
$name = AppSettings::connection('tenant_abc')
    ->get('school.name');
```

### Connections with Contexts and Groups

```
AppSettings::connection('tenant_abc')
    ->context('school', 10)
    ->group('report_cards')
    ->put('show_photo', true);
```

### How Connection Works

The connection is passed to the Eloquent model via `setConnection()`:

```
$model = new Setting();
$model->setConnection('tenant_abc');
```

This allows each tenant to have its own database while using the same codebase.

* * *

## Cache Integration

The package implements a **cache-first** resolution strategy through `SettingsResolver`, which integrates with `schoolpalm/cache-store`.

### How Caching Works

1. **Read:** Try cache first → if miss, read from DB → store in cache
2. **Write:** Write to DB first → refresh cache
3. **Delete:** Delete from DB → remove from cache
4. **Flush:** Delete all settings in scope from DB → forget each cached key for that scope

### Cache Context

Cache context is independent from settings context. It controls how cache keys are isolated:

```
use SchoolPalm\AppSettings\Support\SettingsScope;

// Explicit cache context for cache isolation
$scope = new SettingsScope(
    contextType: 'school',
    contextId: 10,
    cacheContext: ['tenant_abc', 'school_123']
);
```

### Configuring Cache

```
// config/app-settings.php
'cache' => [
    'enabled' => env('APP_SETTINGS_CACHE', true),
    'ttl'     => env('APP_SETTINGS_CACHE_TTL', 3600), // 1 hour
],
```

### Cache Resolution Flow

```
get('school.name')
      │
      ▼
CacheStore::remember('school.name', TTL, function() {
      │
      ▼
    SettingsRepository::get(scope, 'school.name')
      │
      ▼
    Database query
      │
      ▼
    Return value → stored in cache
});
```

* * *

## Bulk Operations

### Store Multiple Settings (`putMany`)

The `SettingsBuilder` provides `putMany()` for batch operations:

```
use SchoolPalm\AppSettings\Facades\AppSettings;

AppSettings::context('school', 1)
    ->putMany([
        'name'       => 'Emma High School',
        'email'      => 'info@emma.sc.ug',
        'phone'      => '+256700000000',
        'is_active'  => true,
        'enrollment' => 1200,
    ]);
```

This is equivalent to calling `put()` for each key-value pair individually.

* * *

## Setting Value Types

The package supports all common PHP types through JSON serialization:

| Type      | Supported | Stored As    |
| --------- | --------- | ------------ |
| `string`  | ✓         | JSON string  |
| `integer` | ✓         | JSON number  |
| `float`   | ✓         | JSON number  |
| `boolean` | ✓         | JSON boolean |
| `array`   | ✓         | JSON array   |
| `object`  | ✓         | JSON object  |
| `null`    | ✓         | JSON null    |

### Examples

```
AppSettings::put('name', 'Emma High');           // string
AppSettings::put('count', 150);                  // integer
AppSettings::put('score', 95.5);                 // float
AppSettings::put('active', true);                // boolean
AppSettings::put('grades', ['A', 'B', 'C']);      // array
AppSettings::put('metadata', ['year' => 2024]);   // associative array
AppSettings::put('theme', null);                 // null
```

Values are cast to JSON when stored and decoded when retrieved, preserving type information.

* * *

## Exception Handling

The package throws `SchoolPalm\AppSettings\Exceptions\SettingsException` for various error conditions.

### Exception Types

#### `invalidKey(string $key)`

Thrown when an empty or invalid key is provided:

```
use SchoolPalm\AppSettings\Exceptions\SettingsException;

try {
    AppSettings::put('', 'value');
} catch (SettingsException $e) {
    echo $e->getMessage();
    // "Invalid settings key provided: "". Keys must be non-empty strings."
}
```

#### `unsupportedValue(mixed $value)`

Thrown when an unsupported value type is stored:

```
try {
    AppSettings::put('key', fopen('file.txt', 'r')); // resource
} catch (SettingsException $e) {
    echo $e->getMessage();
    // "Unsupported settings value type: "resource". Only string, int, float, bool, array, null, and serializable objects are supported."
}
```

#### `missingService()`

Thrown when no `SettingsService` implementation is registered (legacy contract):

```
try {
    // Attempt to use AppSettings without a registered service
} catch (SettingsException $e) {
    echo $e->getMessage();
    // "A SettingsService implementation must be registered before using AppSettings."
}
```

### Best Practices

```
use SchoolPalm\AppSettings\Facades\AppSettings;
use SchoolPalm\AppSettings\Exceptions\SettingsException;

try {
    $value = AppSettings::get('some.key', 'default');
    AppSettings::put('another.key', $value);
} catch (SettingsException $e) {
    report($e);
    // Handle gracefully
}
```

* * *

## Migrations

### Database Table Structure

The `settings` table schema:

| Column         | Type                          | Description                              |
| -------------- | ----------------------------- | ---------------------------------------- |
| `id`           | bigInteger (PK)               | Primary key                              |
| `context_type` | string (nullable)             | Context namespace (school, branch, etc.) |
| `context_id`   | unsignedBigInteger (nullable) | Context identifier                       |
| `group`        | string (nullable)             | Settings group name                      |
| `key`          | string                        | Setting key                              |
| `value`        | json (nullable)               | Setting value (supports all types)       |
| `created_at`   | timestamp                     | Creation timestamp                       |
| `updated_at`   | timestamp                     | Last update timestamp                    |

### Indexes

- **Unique:** `(context_type, context_id, group, key)` — Prevents duplicate settings in the same scope
- **Index:** `(context_type, context_id)` — Optimizes context-scoped queries
- **Index:** `(group)` — Optimizes group-scoped queries

### Publishing Migrations

```
# Standard application
php artisan vendor:publish --tag=app-settings-migrations

# Multi-tenant application
php artisan vendor:publish --tag=app-settings-tenant-migrations

# Run migrations
php artisan migrate
```

* * *

## Testing

### Test Setup

The package uses Pest PHP for testing with Orchestra Testbench.

```
// Basic CRUD test
it('stores and retrieves a setting', function () {
    AppSettings::put('school.name', 'Emma High School');

    expect(AppSettings::get('school.name'))
        ->toBe('Emma High School');
});

// Default value test
it('returns default value when setting does not exist', function () {
    expect(AppSettings::get('school.motto', 'Excellence'))
        ->toBe('Excellence');
});

// Dot-notation shorthand path test
it('retrieves dot-notation path using settings()', function () {
    AppSettings::group('message_delivery.email.laravel-mail')->put('enabled', true);

    expect(AppSettings::settings('message_delivery.email.laravel-mail.enabled'))
        ->toBeTrue();
});

// Existence check test
it('checks if a setting exists', function () {
    AppSettings::put('timezone', 'Africa/Kampala');

    expect(AppSettings::has('timezone'))->toBeTrue();
});
```

### Context Isolation Tests

```
it('isolates settings by context', function () {
    AppSettings::context('school', 1)->put('name', 'School One');
    AppSettings::context('school', 2)->put('name', 'School Two');

    expect(AppSettings::context('school', 1)->get('name'))
        ->toBe('School One');
});
```

### Group Tests

```
it('stores settings inside a group', function () {
    AppSettings::group('report_cards')->put('show_photo', true);

    expect(AppSettings::group('report_cards')->get('show_photo'))
        ->toBeTrue();
});
```

### Running Tests

```
composer test
```

* * *

## Complete API Reference

### `AppSettings` Facade

The facade proxies all calls to `SettingsManager`.

| Method                                                  | Description                                                | Returns                    |
| ------------------------------------------------------- | ---------------------------------------------------------- | -------------------------- |
| `get(string $key, mixed $default = null)`               | Retrieve a setting value                                   | `mixed`                    |
| `settings(?string $path = null, mixed $default = null)` | Retrieve a setting value or path directly via dot-notation | `mixed`                    |
| `setting(?string $path = null, mixed $default = null)`  | Alias for `settings()`                                     | `mixed`                    |
| `put(string $key, mixed $value)`                        | Store a setting value                                      | `SettingsManager` (fluent) |
| `has(string $key)`                                      | Check if a setting exists                                  | `bool`                     |
| `forget(string $key)`                                   | Delete a setting                                           | `SettingsManager` (fluent) |
| `all()`                                                 | Retrieve all settings in current scope                     | `array`                    |
| `flush()`                                               | Delete all settings in current scope                       | `SettingsManager` (fluent) |
| `connection(?string $connection)`                       | Set database connection                                    | `SettingsBuilder`          |
| `context(string $type, string                           | int                                                        | null $identifier = null)`  | Set settings context | `SettingsBuilder` |
| `group(string $group)`                                  | Set settings group                                         | `SettingsBuilder`          |
| `withScope(SettingsScope $scope)`                       | Create builder from existing scope                         | `SettingsBuilder`          |

### `SettingsManager` Methods

| Method                                                  | Description                    | Returns            |
| ------------------------------------------------------- | ------------------------------ | ------------------ |
| `put(string $key, mixed $value)`                        | Store setting                  | `static`           |
| `get(string $key, mixed $default = null)`               | Retrieve setting               | `mixed`            |
| `settings(?string $path = null, mixed $default = null)` | Retrieve setting/path directly | `mixed`            |
| `setting(?string $path = null, mixed $default = null)`  | Alias for `settings()`         | `mixed`            |
| `has(string $key)`                                      | Check existence                | `bool`             |
| `forget(string $key)`                                   | Delete setting                 | `static`           |
| `all()`                                                 | Retrieve all settings          | `array`            |
| `flush()`                                               | Delete all settings            | `static`           |
| `connection(?string $connection)`                       | Set connection                 | `SettingsBuilder`  |
| `context(string $type, string                           | int                            | null $identifier)` | Set context | `SettingsBuilder` |
| `group(string $group)`                                  | Set group                      | `SettingsBuilder`  |
| `withScope(SettingsScope $scope)`                       | Create builder from scope      | `SettingsBuilder`  |

### `SettingsBuilder` Methods

| Method                                                  | Description                    | Returns            |
| ------------------------------------------------------- | ------------------------------ | ------------------ |
| `connection(?string $connection)`                       | Set database connection        | `static`           |
| `context(string $type, string                           | int                            | null $identifier)` | Set context | `static` |
| `group(?string $group)`                                 | Set group                      | `static`           |
| `get(string $key, mixed $default = null)`               | Retrieve setting               | `mixed`            |
| `settings(?string $path = null, mixed $default = null)` | Retrieve setting/path directly | `mixed`            |
| `setting(?string $path = null, mixed $default = null)`  | Alias for `settings()`         | `mixed`            |
| `put(string $key, mixed $value)`                        | Store setting                  | `static`           |
| `putMany(array $settings)`                              | Store multiple settings        | `static`           |
| `has(string $key)`                                      | Check existence                | `bool`             |
| `forget(string $key)`                                   | Delete setting                 | `static`           |
| `all()`                                                 | Retrieve all settings          | `array`            |
| `flush()`                                               | Delete all settings            | `static`           |
| `scope()`                                               | Get current SettingsScope      | `SettingsScope`    |

### `SettingsScope` Methods

| Method                                | Description                      | Returns         |
| ------------------------------------- | -------------------------------- | --------------- |
| `__construct(...)`                    | Create scope with all properties | `void`          |
| `connection()`                        | Get database connection          | `string         | null`                         |
| `contextType()`                       | Get context type                 | `string         | null`                         |
| `contextId()`                         | Get context identifier           | `string         | int                           | null`           |
| `group()`                             | Get settings group               | `string         | null`                         |
| `cacheContext()`                      | Get cache context                | `mixed`         |
| `hasContext()`                        | Whether scope has context        | `bool`          |
| `hasGroup()`                          | Whether scope has group          | `bool`          |
| `withConnection(?string $connection)` | Create scope with new connection | `SettingsScope` |
| `withContext(string $type, string     | int                              | null $id)`      | Create scope with new context | `SettingsScope` |
| `withGroup(?string $group)`           | Create scope with new group      | `SettingsScope` |
| `withCacheContext(mixed $context)`    | Create scope with cache context  | `SettingsScope` |
| `toArray()`                           | Convert scope to array           | `array`         |

### `SettingsException` Static Factory Methods

| Method                           | Description                     |
| -------------------------------- | ------------------------------- |
| `invalidKey(string $key)`        | Exception for invalid keys      |
| `unsupportedValue(mixed $value)` | Exception for unsupported types |
| `missingService()`               | Exception for missing service   |

* * *

## Design Philosophy

**app-settings** is an abstraction layer, not a storage engine.

Its only responsibility is to provide a clean, expressive, and fluent API for working with application settings while delegating persistence to a repository layer.

### Core Principles

1. **Simple Developer API** — Minimal, intuitive, and expressive methods
2. **Database Independent** — Works with any database engine
3. **Tenant Independent** — No built-in tenant assumptions
4. **School Independent** — Flexible context system for any entity

**app-settings** is an abstraction layer, not a storage engine.

Its only responsibility is to provide a clean, expressive, and fluent API for working with application settings while delegating persistence to a repository layer.

### Core Principles

1. **Simple Developer API** — Minimal, intuitive, and expressive methods
2. **Database Independent** — Works with any database engine
3. **Tenant Independent** — No built-in tenant assumptions
4. **School Independent** — Flexible context system for any entity
5. **Storage Independent** — Plug in any storage backend
6. **Cache Independent** — Cache is optional and swappable
7. **Easy to Test** — Clear separation of concerns enables easy mocking
8. **Framework Agnostic** — Works with any Laravel application

### Separation of Responsibilities

| App Settings Package | Application                      |
| -------------------- | -------------------------------- |
| Developer API        | Database connection resolution   |
| Facade & Manager     | Tenant/School context resolution |
| SettingsBuilder      | Cache strategy                   |
| SettingsResolver     | Serialization logic              |
| SettingsRepository   | Cache key generation             |
| SettingsScope        | Business logic                   |
| Exceptions           | Validation rules                 |

### Integration Pattern

```
AppSettings                       (package)
      │
      ▼
SettingsManager                   (package)
      │
      ▼
SettingsBuilder → SettingsScope    (package)
      │
      ▼
SettingsResolver                  (package)
      │
      ├── SettingsRepository      (package) → Database
      └── CacheStore              (optional package) → Cache
```

### Why This Approach?

- **Testability:** Each layer can be tested in isolation
- **Flexibility:** Swap storage without changing application code
- **Reusability:** Use the same API across different projects
- **Maintainability:** Clear boundaries between concerns
- **Future-Proof:** Add new features without breaking existing code

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Contributors

- [Mugabo Hassan](https://github.com/dybarox)

## Support

- **Email:** support@schoolpalm.com
- **Issues:** [GitHub Issues](https://github.com/schoolpalm/app-settings/issues)
- **Source:** [GitHub](https://github.com/schoolpalm/app-settings)

