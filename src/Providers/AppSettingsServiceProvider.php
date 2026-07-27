<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Providers;

use Illuminate\Support\ServiceProvider;
use SchoolPalm\AppSettings\Managers\SettingsManager;
use SchoolPalm\AppSettings\Repositories\SettingsRepository;
use SchoolPalm\AppSettings\Resolvers\SettingsResolver;

/**
 * Class AppSettingsServiceProvider
 *
 * Registers the app-settings package.
 *
 * Responsibilities:
 *
 * - Register settings manager
 * - Register repository
 * - Register resolver
 * - Publish configuration
 * - Publish migrations
 */
final class AppSettingsServiceProvider extends ServiceProvider
{

    /**
     * Register package services.
     *
     * Registers:
     *
     * - SettingsRepository
     * - SettingsResolver
     * - SettingsManager
     */
    public function register(): void
    {

        /*
        |--------------------------------------------------------------------------
        | Settings Repository
        |--------------------------------------------------------------------------
        |
        | Handles database persistence.
        |
        */

        $this->app->singleton(
            SettingsRepository::class
        );



        /*
        |--------------------------------------------------------------------------
        | Settings Resolver
        |--------------------------------------------------------------------------
        |
        | Handles cache-first resolution.
        |
        */

        $this->app->singleton(
            SettingsResolver::class,
            function ($app) {

                return new SettingsResolver(
                    $app->make(
                        SettingsRepository::class
                    )
                );
            }
        );



        /*
        |--------------------------------------------------------------------------
        | Settings Manager
        |--------------------------------------------------------------------------
        |
        | Public API entry point.
        |
        */

        $this->app->singleton(
            SettingsManager::class,
            function ($app) {

                return new SettingsManager(
                    $app->make(
                        SettingsResolver::class
                    )
                );
            }
        );



        /*
        |--------------------------------------------------------------------------
        | Facade Alias
        |--------------------------------------------------------------------------
        */

        $this->app->alias(
            SettingsManager::class,
            'app-settings.manager'
        );
    }



    /**
     * Bootstrap package services.
     *
     * Publishes:
     *
     * - Configuration
     * - Application migrations
     * - Tenant migrations
     */
    public function boot(): void
    {

        /*
        |--------------------------------------------------------------------------
        | Publish Configuration
        |--------------------------------------------------------------------------
        */

        $this->publishes(
            [
                __DIR__ . '/../../config/app-settings.php'
                =>
                config_path('app-settings.php'),

            ],
            'app-settings-config'
        );



        /*
        |--------------------------------------------------------------------------
        | Publish Main Migration
        |--------------------------------------------------------------------------
        */

        $this->publishes(
            [
                __DIR__
                    . '/../../database/migrations/create_settings_table.php'
                =>
                database_path(
                    'migrations/create_settings_table.php'
                ),

            ],
            'app-settings-migrations'
        );



        /*
        |--------------------------------------------------------------------------
        | Publish Tenant Migration
        |--------------------------------------------------------------------------
        */

        $this->publishes(
            [
                __DIR__
                    . '/../../database/migrations/tenants/create_settings_table.php'
                =>
                database_path(
                    'migrations/tenants/create_settings_table.php'
                ),

            ],
            'app-settings-tenant-migrations'
        );
    }
}
