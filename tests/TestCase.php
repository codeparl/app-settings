<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SchoolPalm\AppSettings\Providers\AppSettingsServiceProvider;
use SchoolPalm\CacheStore\Providers\CacheStoreServiceProvider;

abstract class TestCase extends Orchestra
{

    /**
     * Register package service providers.
     */
    protected function getPackageProviders(
        $app
    ): array {

        return [
            AppSettingsServiceProvider::class,
            CacheStoreServiceProvider::class,
        ];
    }



    /**
     * Configure testing environment.
     */
    protected function defineEnvironment(
        $app
    ): void {


        /*
        |--------------------------------------------------------------------------
        | Application
        |--------------------------------------------------------------------------
        */

        $app['config']->set(
            'app.key',
            'base64:' . base64_encode(
                random_bytes(32)
            )
        );



        /*
        |--------------------------------------------------------------------------
        | Database
        |--------------------------------------------------------------------------
        */

        $app['config']->set(
            'database.default',
            'testing'
        );


        $app['config']->set(
            'database.connections.testing',
            [

                'driver' => 'sqlite',

                'database' => ':memory:',

                'prefix' => '',

                'foreign_key_constraints' => true,

            ]
        );



        /*
        |--------------------------------------------------------------------------
        | Cache
        |--------------------------------------------------------------------------
        */

        $app['config']->set(
            'cache.default',
            'array'
        );


        /*
        |--------------------------------------------------------------------------
        | App Settings
        |--------------------------------------------------------------------------
        */

        $app['config']->set(
            'app-settings.cache.ttl',
            3600
        );
    }




    /**
     * Perform test setup.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();
        \SchoolPalm\CacheStore\Facades\CacheStore::flush();
    }




    /**
     * Load package migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/../database/migrations'
        );
    }
}
