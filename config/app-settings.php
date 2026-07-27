<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection used when no connection is explicitly provided.
    |
    | This allows the package to work in normal Laravel applications without
    | requiring multi-tenancy.
    |
    | Examples:
    |
    | mysql
    | sqlite
    | tenant
    |
    | In SchoolPalm this can normally remain null because tenant connection
    | is provided dynamically.
    |
    */

    'connection' => env(
        'APP_SETTINGS_CONNECTION',
        config('database.default')
    ),


    /*
    |--------------------------------------------------------------------------
    | Default Context
    |--------------------------------------------------------------------------
    |
    | Optional default context applied when no context is explicitly provided.
    |
    | Example:
    |
    | [
    |     'type' => 'school',
    |     'id'   => 1,
    | ]
    |
    */

    'context' => null,


    /*
    |--------------------------------------------------------------------------
    | Default Group
    |--------------------------------------------------------------------------
    |
    | Optional default settings group.
    |
    */

    'group' => null,


    /*
    |--------------------------------------------------------------------------
    | Settings Key Separator
    |--------------------------------------------------------------------------
    */

    'key_separator' => env(
        'APP_SETTINGS_KEY_SEPARATOR',
        '.'
    ),


    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache implementation is provided by cache-store.
    |
    */

    'cache' => [

        'enabled' => env(
            'APP_SETTINGS_CACHE',
            true
        ),

        'ttl' => env(
            'APP_SETTINGS_CACHE_TTL',
            3600
        ),

    ],


    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    */

    'defaults' => [],


    /*
    |--------------------------------------------------------------------------
    | Migration Mode
    |--------------------------------------------------------------------------
    |
    | Controls the migration target.
    |
    | central:
    |   Standard Laravel application.
    |
    | tenant:
    |   Multi-tenant applications.
    |
    */

    'migration' => [

        'mode' => env(
            'APP_SETTINGS_MIGRATION_MODE',
            'central'
        ),

    ],

];
