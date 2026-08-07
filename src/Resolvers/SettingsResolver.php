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
                $this->cacheKey($scope, $key),
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
                $this->cacheKey($scope, $key),
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
            ->forget(
                $this->cacheKey($scope, $key)
            );
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
     *
     * Clears both the database and the cache for the
     * given scope (context and group).
     *
     * The database is flushed first, then each cached
     * key belonging to the scope is forgotten using the
     * group-aware cache key so stale values do not bleed
     * into subsequent reads.
     */
    public function flush(
        SettingsScope $scope
    ): void {

        /*
         * Capture the keys before deleting from storage
         * so they can be used to invalidate the cache.
         */
        $keys = array_keys(
            $this->repository->all($scope)
        );


        $this->repository->flush(
            $scope
        );


        $cache = $this->cache($scope);

        foreach ($keys as $key) {

            $cache->forget(
                $this->cacheKey($scope, $key)
            );
        }
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




    /**
     * Build a group-aware cache key.
     *
     * The cache-store key builder only incorporates tenant and
     * school contexts. To prevent group bleed within the same
     * context, the group is prefixed onto the base key.
     *
     * Example:
     *
     * email:provider
     * sms:provider
     *
     * @param SettingsScope $scope
     * @param string $key
     *
     * @return string
     */
    protected function cacheKey(
        SettingsScope $scope,
        string $key
    ): string {

        if ($scope->hasGroup()) {

            return $scope->group() . ':' . $key;
        }


        return $key;
    }
}
