# SchoolPalm App Settings — Usage & API Documentation

This document is the definitive guide to using the `schoolpalm/app-settings` package. It covers every public API, the settings scope model, context and group isolation, cache integration (including the group cache-key fix), and practical examples.

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Installation & Setup](#installation--setup)
4. [The Settings Scope](#the-settings-scope)
5. [Basic CRUD Operations](#basic-crud-operations)
6. [Context Isolation](#context-isolation)
7. [Settings Groups](#settings-groups)
8. [Database Connections](#database-connections)
9. [Cache Integration & Key Generation](#cache-integration--key-generation)
10. [Bulk Operations](#bulk-operations)
11. [Setting Value Types](#setting-value-types)
12. [Exception Handling](#exception-handling)
13. [Testing](#testing)
14. [Complete API Reference](#complete-api-reference)
15. [Design Philosophy](#design-philosophy)

---

## Overview

A lightweight, storage-agnostic settings package for Laravel. It provides a consistent, expressive API for reading and writing application settings — without coupling your application to any storage engine, database engine, or caching mechanism.

### What the package does *not* know about

- Database engines
- Eloquent models
- Tenants / schools
- Caching mechanisms (Redis, Laravel Cache, etc.)
- Cache key generation

All persistence is delegated to a layered architecture:

```
Facade → Manager → Builder → Resolver → Repository
```

---

## Architecture

### Layer Responsibilities

| Layer                      | Responsibility                                   |
| -------------------------- | ------------------------------------------------ |
| **Facade** (`AppSettings`) | Developer-friendly static API                    |
| **SettingsManager**        | Entry point, delegates to Builder                |
| **SettingsBuilder**        | Fluent API, manages a `SettingsScope`            |
| **SettingsResolver**       | Cache-first resolution strategy                  |
| **SettingsRepository**     | Database persistence via the `Setting` model     |
| **SettingsScope**          | Immutable value object for the operation context |

### Data Flow

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
          Database         Redis / Cache
```

---

## Installation & Setup

### Install via Composer

```bash
composer require schoolpalm/app-settings
```

### Laravel Auto-Discovery

The service provider and facade are auto-discovered:

- **Service Provider:** `SchoolPalm\AppSettings\Providers\AppSettingsServiceProvider`
- **Facade:** `SchoolPalm\AppSettings\Facades\AppSettings`

### Publish Configuration

```bash
php artisan vendor:publish --tag=app-settings-config
```

### Publish Migrations

```bash
# Standard applications
php artisan vendor:publish --tag=app-settings-migrations

# Multi-tenant applications
php artisan vendor:publish --tag=app-settings-tenant-migrations

# Run migrations
php artisan migrate
```

### Optional Cache Package

Cache-first resolution is provided by `schoolpalm/cache-store`:

```bash
composer require schoolpalm/cache-store
```

---

## The Settings Scope

`SettingsScope` is an **immutable value object** that defines the isolation boundaries of a setting operation. Every modifier method returns a **new** instance; the original is never mutated.

### Scope Properties

| Property       | Type                | Description                                  |
| -------------- | ------------------- | -------------------------------------------- |
| `connection`   | `string             | null`                                        | Database connection name                    |
| `contextType`  | `string             | null`                                        | Context namespace (e.g. `school`, `branch`) |
| `contextId`    | `string\|int\|null` | Context identifier                           |
| `group`        | `string             | null`                                        | Settings group (e.g. `report_cards`)        |
| `cacheContext` | `mixed\|null`       | Cache isolation context passed to CacheStore |

### Creating a Scope

```php
use SchoolPalm\AppSettings\Support\SettingsScope;

// Global scope (no isolation)
$global = new SettingsScope();

// School-specific
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

### Immutable Modification

```php
$scope = new SettingsScope();

$tenantScope = $scope->withConnection('tenant');       // new instance
$schoolScope = $tenantScope->withContext('school', 10); // new instance
$groupScope  = $schoolScope->withGroup('report_cards'); // new instance
$cachedScope = $groupScope->withCacheContext(['tenant_abc', 'school_123']);

// $scope remains unchanged
```

### Scope Accessors

```php
$scope->connection();      // ?string
$scope->contextType();     // ?string
$scope->contextId();       // string|int|null
$scope->group();           // ?string
$scope->cacheContext();    // mixed
$scope->hasContext();      // bool
$scope->hasGroup();        // bool
$scope->toArray();         // array
```

---

## Basic CRUD Operations

### Store a Setting — `put`

```php
AppSettings::put('school.name', 'Emma High School');
AppSettings::put('school.enrollment', 1200);
AppSettings::put('school.is_active', true);
AppSettings::put('school.subjects', ['Math', 'Science', 'English']);
```

`put()` returns `$this` for chaining:

```php
AppSettings::put('school.name', 'Emma High')
    ->put('school.email', 'info@emma.sc.ug')
    ->put('school.phone', '+256700000000');
```

### Retrieve a Setting — `get`

```php
$name = AppSettings::get('school.name');

// With a default value
$motto = AppSettings::get('school.motto', 'Excellence');

// Type information is preserved
$enrollment = AppSettings::get('school.enrollment'); // int 1200
$isActive   = AppSettings::get('school.is_active');  // bool true
```

### Check Existence — `has`

```php
if (AppSettings::has('school.name')) {
    // exists in storage
}
```

> **Note:** `has()` checks storage only. It does **not** consider default values.

### Delete a Setting — `forget`

```php
AppSettings::forget('school.logo')
    ->forget('school.banner');
```

### Retrieve All — `all`

```php
$settings = AppSettings::all();
// ['school.name' => 'Emma High', 'school.enrollment' => 1200, ...]
```

### Delete All — `flush`

`flush()` removes **all settings in the current scope** from **both the database and the cache**, preventing stale cached values from bleeding into subsequent reads.

```php
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

---

## Context Isolation

Contexts isolate settings for specific entities (schools, branches, users, departments, etc.).

### Why Contexts?

Without a context, settings are **global**. Contexts enable multi-tenant / multi-entity settings where each entity has its own values.

### Using Contexts

```php
AppSettings::context('school', 1)->put('name', 'School One');
AppSettings::context('school', 2)->put('name', 'School Two');

$school1Name = AppSettings::context('school', 1)->get('name'); // 'School One'
$school2Name = AppSettings::context('school', 2)->get('name'); // 'School Two'
```

### How It Works

A context translates to database filters:

```sql
WHERE context_type = 'school'
  AND context_id = 10
```

Without a context:

```sql
WHERE context_type IS NULL
  AND context_id IS NULL
```

### Any Context Type

```php
AppSettings::context('branch', 5)->put('currency', 'UGX');
AppSettings::context('user', 42)->put('theme', 'dark');
AppSettings::context('department', 'engineering')->put('lead', 'John');
```

---

## Settings Groups

Groups organize related settings. They are **independent of, and composable with**, context isolation.

### Using Groups

```php
AppSettings::group('report_cards')->put('show_photo', true);
AppSettings::group('report_cards')->put('show_signature', false);

$showPhoto = AppSettings::group('report_cards')->get('show_photo'); // true

$reportCardSettings = AppSettings::group('report_cards')->all();
// ['show_photo' => true, 'show_signature' => false]
```

### Groups + Contexts

```php
// School-specific report card settings
AppSettings::context('school', 10)
    ->group('report_cards')
    ->put('show_photo', true);

// School-specific grading settings
AppSettings::context('school', 10)
    ->group('grading')
    ->put('pass_mark', 50);
```

### Groups are Fully Isolated

Groups are isolated **within the same context** and across contexts. Two groups never bleed into each other, even when they store values under the same key:

```php
$context = AppSettings::context('school', 1);

$context->group('message_delivery.email')->put('provider', 'smtp');
$context->group('message_delivery.sms')->put('provider', 'meta');

// Each group returns its own value, even within the same context
$context->group('message_delivery.email')->get('provider'); // 'smtp'
$context->group('message_delivery.sms')->get('provider');   // 'meta'
```

And for complex array values:

```php
$emailConfig = ['host' => 'smtp.school.com', 'token' => 'email_token'];
$whatsappConfig = ['host' => 'wa.school.com', 'token' => 'whatsapp_token'];

AppSettings::group('message_delivery.email')->put('config', $emailConfig);
AppSettings::group('message_delivery.whatsapp')->put('config', $whatsappConfig);

AppSettings::group('message_delivery.email')->get('config');    // $emailConfig
AppSettings::group('message_delivery.whatsapp')->get('config');  // $whatsappConfig
```

### Common Group Use Cases

- `report_cards` — Report card display settings
- `grading` — Grade calculation rules
- `sms` / `message_delivery.email` / `message_delivery.whatsapp` — Messaging configuration
- `payroll` — Payroll configuration
- `theme` — Application theming
- `notifications` — Notification preferences

---

## Database Connections

The package supports dynamic database connections for tenant isolation at the database level.

### Using Connections

```php
AppSettings::put('app.name', 'My App'); // default connection

AppSettings::connection('tenant_abc')
    ->put('school.name', 'Emma High');

$name = AppSettings::connection('tenant_abc')
    ->get('school.name');
```

### Connections + Contexts + Groups

```php
AppSettings::connection('tenant_abc')
    ->context('school', 10)
    ->group('report_cards')
    ->put('show_photo', true);
```

### How It Works

The connection is applied to the Eloquent model via `setConnection()`:

```php
$model = new Setting();
$model->setConnection('tenant_abc');
```

This allows each tenant to have its own database while sharing the same codebase.

---

## Cache Integration & Key Generation

The package uses a **cache-first** resolution strategy via `SettingsResolver` and `schoolpalm/cache-store`.

### Cache Flow

1. **Read:** Try cache → on miss read DB → store in cache
2. **Write:** Write to DB first → refresh cache
3. **Delete:** Delete from DB → remove from cache
4. **Flush:** Delete all settings in scope from DB → forget each cached key for that scope

### Cache Context

Cache context is independent from the settings context and controls how cache keys are isolated:

```php
use SchoolPalm\AppSettings\Support\SettingsScope;

$scope = new SettingsScope(
    contextType: 'school',
    contextId: 10,
    cacheContext: ['tenant_abc', 'school_123']
);

$builder = AppSettings::withScope($scope);
$value = $builder->get('school.name');
```

### Cache Key Generation & Group Isolation

Cache keys are constructed from three layers:

1. **Group** — prefixed directly onto the base key when the scope has a group.
2. **Context** — applied by `CacheStore` using the scope's context type/id (or an explicit `cacheContext`).
3. **Prefix** — the CacheStore global prefix (default `schoolpalm`).

**Group-only key:**

```
group:key
# e.g. report_cards:show_photo
```

**Group + context key** (after CacheStore applies the context):

```
schoolpalm:school:1:report_cards:show_photo
```

**Context-only key** (no group):

```
schoolpalm:school:1:school.name
```

The group is prefixed onto the base cache key **before** CacheStore applies the context. This is what guarantees that two groups sharing the same context and the same setting key never collide:

| Group                    | Cache key                                             |
| ------------------------ | ----------------------------------------------------- |
| `message_delivery.email` | `schoolpalm:school:1:message_delivery.email:provider` |
| `message_delivery.sms`   | `schoolpalm:school:1:message_delivery.sms:provider`   |

Because these keys differ, writing `provider` to the SMS group can never overwrite the cached `provider` value of the email group, and vice versa. This is the fix that prevents group bleed within the same context.

### Configuring Cache

```php
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
CacheStore::remember('school:1:school.name', TTL, function() {
      │
      ▼
    SettingsRepository::get($scope, 'school.name')
      │
      ▼
    Database query (already filtered by context + group)
      │
      ▼
    Return value → stored in cache
});
```

---

## Bulk Operations

`SettingsBuilder::putMany()` stores multiple settings at once:

```php
AppSettings::context('school', 1)
    ->putMany([
        'name'       => 'Emma High School',
        'email'      => 'info@emma.sc.ug',
        'phone'      => '+256700000000',
        'is_active'  => true,
        'enrollment' => 1200,
    ]);
```

This is equivalent to calling `put()` for each key-value pair.

---

## Setting Value Types

All common PHP types are supported through JSON serialization:

| Type      | Supported | Stored As    |
| --------- | --------- | ------------ |
| `string`  | ✅         | JSON string  |
| `integer` | ✅         | JSON number  |
| `float`   | ✅         | JSON number  |
| `boolean` | ✅         | JSON boolean |
| `array`   | ✅         | JSON array   |
| `object`  | ✅         | JSON object  |
| `null`    | ✅         | JSON null    |

### Examples

```php
AppSettings::put('name', 'Emma High');          // string
AppSettings::put('count', 150);                 // integer
AppSettings::put('score', 95.5);                // float
AppSettings::put('active', true);               // boolean
AppSettings::put('grades', ['A', 'B', 'C']);    // array
AppSettings::put('metadata', ['year' => 2024]); // associative array
AppSettings::put('theme', null);                // null
```

Values are cast to JSON when stored and decoded on retrieval, preserving type information.

---

## Exception Handling

The package throws `SchoolPalm\AppSettings\Exceptions\SettingsException`.

### Factory Methods

#### `invalidKey(string $key)`

```php
use SchoolPalm\AppSettings\Exceptions\SettingsException;

try {
    AppSettings::put('', 'value');
} catch (SettingsException $e) {
    echo $e->getMessage();
    // 'Invalid settings key provided: "". Keys must be non-empty strings.'
}
```

#### `unsupportedValue(mixed $value)`

```php
try {
    AppSettings::put('key', fopen('file.txt', 'r')); // resource
} catch (SettingsException $e) {
    echo $e->getMessage();
    // 'Unsupported settings value type: "resource". Only string, int, float, bool, array, null, and serializable objects are supported.'
}
```

#### `missingService()`

```php
try {
    // no registered SettingsService implementation
} catch (SettingsException $e) {
    echo $e->getMessage();
    // 'A SettingsService implementation must be registered before using AppSettings.'
}
```

### Best Practice

```php
use SchoolPalm\AppSettings\Facades\AppSettings;
use SchoolPalm\AppSettings\Exceptions\SettingsException;

try {
    $value = AppSettings::get('some.key', 'default');
    AppSettings::put('another.key', $value);
} catch (SettingsException $e) {
    report($e);
}
```

---

## Testing

The package uses Pest PHP with Orchestra Testbench.

### Basic CRUD

```php
it('stores and retrieves a setting', function () {
    AppSettings::put('school.name', 'Emma High School');

    expect(AppSettings::get('school.name'))
        ->toBe('Emma High School');
});

it('returns default value when setting does not exist', function () {
    expect(AppSettings::get('school.motto', 'Excellence'))
        ->toBe('Excellence');
});

it('checks if a setting exists', function () {
    AppSettings::put('timezone', 'Africa/Kampala');

    expect(AppSettings::has('timezone'))->toBeTrue();
});
```

### Context Isolation

```php
it('isolates settings by context', function () {
    AppSettings::context('school', 1)->put('name', 'School One');
    AppSettings::context('school', 2)->put('name', 'School Two');

    expect(AppSettings::context('school', 1)->get('name'))
        ->toBe('School One');
});
```

### Group Isolation (same context)

```php
it('isolates settings by group within the same context', function () {
    $context = AppSettings::context('school', 1);

    $context->group('message_delivery.email')->put('provider', 'smtp');
    $context->group('message_delivery.sms')->put('provider', 'meta');

    expect($context->group('message_delivery.email')->get('provider'))
        ->toBe('smtp');

    expect($context->group('message_delivery.sms')->get('provider'))
        ->toBe('meta');
});

it('prevents group configuration bleed when setting complex arrays', function () {
    $emailConfig    = ['host' => 'smtp.school.com'];
    $whatsappConfig = ['host' => 'wa.school.com'];

    AppSettings::group('message_delivery.email')->put('config', $emailConfig);
    AppSettings::group('message_delivery.whatsapp')->put('config', $whatsappConfig);

    expect(AppSettings::group('message_delivery.email')->get('config'))
        ->toBe($emailConfig)
        ->and(AppSettings::group('message_delivery.whatsapp')->get('config'))
        ->toBe($whatsappConfig);
});
```

### Flush (Cache + DB)

```php
it('flushes settings from cache and database for a context', function () {
    AppSettings::context('school', 1)->put('name', 'School One');

    // Warm the cache
    expect(AppSettings::context('school', 1)->get('name'))
        ->toBe('School One');

    AppSettings::context('school', 1)->flush();

    // Gone from both cache and DB
    expect(AppSettings::context('school', 1)->get('name'))
        ->toBeNull();

    $this->assertDatabaseMissing('settings', [
        'context_type' => 'school',
        'context_id'   => '1',
        'key'          => 'name',
    ]);
});

it('flush only affects the targeted group within a context', function () {
    $context = AppSettings::context('school', 1);

    $context->group('message_delivery.email')->put('provider', 'smtp');
    $context->group('message_delivery.sms')->put('provider', 'meta');

    $context->group('message_delivery.email')->flush();

    expect($context->group('message_delivery.email')->get('provider'))
        ->toBeNull();

    expect($context->group('message_delivery.sms')->get('provider'))
        ->toBe('meta');
});
```

### Run Tests

```bash
vendor/bin/pest
```

---

## Complete API Reference

### `AppSettings` Facade

Proxies all calls to `SettingsManager`.

| Method                                                        | Description                            | Returns                    |
| ------------------------------------------------------------- | -------------------------------------- | -------------------------- |
| `get(string $key, mixed $default = null)`                     | Retrieve a setting value               | `mixed`                    |
| `put(string $key, mixed $value)`                              | Store a setting value                  | `SettingsManager` (fluent) |
| `has(string $key)`                                            | Check if a setting exists              | `bool`                     |
| `forget(string $key)`                                         | Delete a setting                       | `SettingsManager` (fluent) |
| `all()`                                                       | Retrieve all settings in current scope | `array`                    |
| `flush()`                                                     | Delete all settings in current scope   | `SettingsManager` (fluent) |
| `connection(?string $connection)`                             | Set database connection                | `SettingsBuilder`          |
| `context(string $type, string\|int\|null $identifier = null)` | Set settings context                   | `SettingsBuilder`          |
| `group(string $group)`                                        | Set settings group                     | `SettingsBuilder`          |
| `withScope(SettingsScope $scope)`                             | Create builder from existing scope     | `SettingsBuilder`          |

### `SettingsManager` Methods

| Method                                                 | Description               | Returns           |
| ------------------------------------------------------ | ------------------------- | ----------------- |
| `put(string $key, mixed $value)`                       | Store setting             | `static`          |
| `get(string $key, mixed $default = null)`              | Retrieve setting          | `mixed`           |
| `has(string $key)`                                     | Check existence           | `bool`            |
| `forget(string $key)`                                  | Delete setting            | `static`          |
| `all()`                                                | Retrieve all settings     | `array`           |
| `flush()`                                              | Delete all settings       | `static`          |
| `connection(?string $connection)`                      | Set connection            | `SettingsBuilder` |
| `context(string $type, string\|int\|null $identifier)` | Set context               | `SettingsBuilder` |
| `group(string $group)`                                 | Set group                 | `SettingsBuilder` |
| `withScope(SettingsScope $scope)`                      | Create builder from scope | `SettingsBuilder` |

### `SettingsBuilder` Methods

| Method                                                 | Description                 | Returns         |
| ------------------------------------------------------ | --------------------------- | --------------- |
| `connection(?string $connection)`                      | Set database connection     | `static`        |
| `context(string $type, string\|int\|null $identifier)` | Set context                 | `static`        |
| `group(?string $group)`                                | Set group                   | `static`        |
| `get(string $key, mixed $default = null)`              | Retrieve setting            | `mixed`         |
| `put(string $key, mixed $value)`                       | Store setting               | `static`        |
| `putMany(array $settings)`                             | Store multiple settings     | `static`        |
| `has(string $key)`                                     | Check existence             | `bool`          |
| `forget(string $key)`                                  | Delete setting              | `static`        |
| `all()`                                                | Retrieve all settings       | `array`         |
| `flush()`                                              | Delete all settings         | `static`        |
| `scope()`                                              | Get current `SettingsScope` | `SettingsScope` |

### `SettingsScope` Methods

| Method                                             | Description                      | Returns             |
| -------------------------------------------------- | -------------------------------- | ------------------- |
| `__construct(...)`                                 | Create scope with all properties | `void`              |
| `connection()`                                     | Get database connection          | `string             | null` |
| `contextType()`                                    | Get context type                 | `string             | null` |
| `contextId()`                                      | Get context identifier           | `string\|int\|null` |
| `group()`                                          | Get settings group               | `string             | null` |
| `cacheContext()`                                   | Get cache context                | `mixed`             |
| `hasContext()`                                     | Whether scope has context        | `bool`              |
| `hasGroup()`                                       | Whether scope has group          | `bool`              |
| `withConnection(?string $connection)`              | Create scope with new connection | `SettingsScope`     |
| `withContext(string $type, string\|int\|null $id)` | Create scope with new context    | `SettingsScope`     |
| `withGroup(?string $group)`                        | Create scope with new group      | `SettingsScope`     |
| `withCacheContext(mixed $context)`                 | Create scope with cache context  | `SettingsScope`     |
| `toArray()`                                        | Convert scope to array           | `array`             |

### `SettingsException` Static Factory Methods

| Method                           | Description                     |
| -------------------------------- | ------------------------------- |
| `invalidKey(string $key)`        | Exception for invalid keys      |
| `unsupportedValue(mixed $value)` | Exception for unsupported types |
| `missingService()`               | Exception for missing service   |

---

## Design Philosophy

This package is an **abstraction layer**, not a storage engine. Its only responsibility is a clean, expressive, fluent API for working with settings while delegating persistence to a repository.

### Core Principles

1. **Simple Developer API** — Minimal, intuitive, expressive methods
2. **Database Independent** — Works with any database engine
3. **Tenant Independent** — No built-in tenant assumptions
4. **School Independent** — Flexible context system for any entity
5. **Storage Independent** — Plug in any storage backend
6. **Cache Independent** — Cache is optional and swappable
7. **Easy to Test** — Clear separation of concerns
8. **Clean Group Isolation** — Groups are fully isolated at both the storage and cache layers

### Separation of Responsibilities

| App Settings Package | Application                      |
| -------------------- | -------------------------------- |
| Developer API        | Database connection resolution   |
| Facade & Manager     | Tenant/School context resolution |
| SettingsBuilder      | Cache strategy                   |
| SettingsResolver     | Serialization logic              |
| SettingsRepository   | Persistence (`Setting` model)    |
| SettingsScope        | Operation context (immutable)    |
| Exceptions           | Validation rules                 |

---

## License

This package is open-sourced software licensed under the [MIT license](../LICENSE).
