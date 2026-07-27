<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Resolvers;

use SchoolPalm\AppSettings\Repositories\SettingsRepository;
use SchoolPalm\AppSettings\Support\SettingsScope;
use SchoolPalm\CacheStore\Facades\CacheStore;

/**
 * Class SettingsResolver
 *
 * Resolves settings using cache-first strategy.
 *
 * Cache isolation and key resolution are delegated
 * to cache-store.
 *
 * This resolver does not know about:
 *
 * - tenants
 * - schools
 * - cache namespaces
 * - cache key generation
 */
class SettingsResolver
{

    public function __construct(
        protected SettingsRepository $repository
    ) {}



    public function get(
        SettingsScope $scope,
        string $key,
        mixed $default = null
    ): mixed {

        return $this->cache($scope)
            ->remember(
                $key,
                config('app-settings.cache.ttl'),
                function () use (
                    $scope,
                    $key,
                    $default
                ) {

                    return $this->repository->get(
                        $scope,
                        $key,
                        $default
                    );
                }
            );
    }




    public function put(
        SettingsScope $scope,
        string $key,
        mixed $value
    ): void {

        $this->repository->put(
            $scope,
            $key,
            $value
        );


        $this->cache($scope)
            ->put(
                $key,
                $value,
                config('app-settings.cache.ttl')
            );
    }




    public function forget(
        SettingsScope $scope,
        string $key
    ): void {

        $this->repository->forget(
            $scope,
            $key
        );


        $this->cache($scope)
            ->forget($key);
    }




    /**
     * Determine if setting exists.
     *
     * Note:
     * This checks storage only.
     * It does not consider defaults.
     */
    public function has(
        SettingsScope $scope,
        string $key
    ): bool {

        return $this->repository->has(
            $scope,
            $key
        );
    }




    /**
     * Retrieve all settings in current scope.
     *
     * @return array<string,mixed>
     */
    public function all(
        SettingsScope $scope
    ): array {

        return $this->repository->all(
            $scope
        );
    }




    /**
     * Remove all settings in current scope.
     */
    public function flush(
        SettingsScope $scope
    ): void {

        $this->repository->flush(
            $scope
        );

        /*
         * Cache invalidation of a complete scope
         * will be handled when cache-store supports
         * context flushing.
         */
    }




    /**
     * Resolve cache instance for scope.
     */
    protected function cache(
        SettingsScope $scope
    ): mixed {

        /**
         * Explicit cache context provided by application.
         *
         * Example:
         *
         * tenant_abc
         * school_123
         */
        if ($scope->cacheContext() !== null) {

            return CacheStore::forContext(
                ...$scope->cacheContext()
            );
        }



        /**
         * Fallback cache context.
         *
         * Uses settings scope only.
         *
         * Does not know about tenants.
         *
         * Example:
         *
         * school:10
         */
        if ($scope->contextType() !== null) {

            return CacheStore::forContext(
                $scope->contextType(),
                (string) $scope->contextId()
            );
        }



        /**
         * Global settings.
         */
        return CacheStore::getFacadeRoot();
    }
}
