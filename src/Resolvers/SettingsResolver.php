<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Resolvers;

use SchoolPalm\AppSettings\Repositories\SettingsRepository;
use SchoolPalm\AppSettings\Support\SettingsScope;
use SchoolPalm\CacheStore\Facades\CacheStore;

/**
 * Class SettingsResolver
 *
 * Resolves settings using a cache-first strategy.
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

    /**
     * Retrieve a setting value using cache.
     */
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

    /**
     * Store a setting value in database and update cache.
     */
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

        $this->invalidateGroupCache($scope);
    }

    /**
     * Remove a setting from database and cache.
     */
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

        $this->invalidateGroupCache($scope);
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
     * Retrieve all settings in current scope as a cached nested array.
     *
     * @return array<string,mixed>
     */
    public function all(
        SettingsScope $scope
    ): array {
        return $this->cache($scope)
            ->remember(
                $this->groupCacheKey($scope),
                config('app-settings.cache.ttl'),
                function () use ($scope) {
                    return $this->repository->all($scope);
                }
            );
    }

    /**
     * Remove all settings in current scope.
     *
     * Clears both the database and the cache for the
     * given scope (context and group).
     */
    public function flush(
        SettingsScope $scope
    ): void {
        $keys = array_keys(
            $this->repository->all($scope)
        );

        $this->repository->flush(
            $scope
        );

        $cache = $this->cache($scope);

        // Clear parent / group-level aggregated cache structure
        $cache->forget($this->groupCacheKey($scope));

        // Flush individual item caches
        foreach ($keys as $key) {
            $cache->forget(
                $this->cacheKey($scope, $key)
            );
        }
    }

    /**
     * Invalidate cached group structure when writing or forgetting items.
     */
    protected function invalidateGroupCache(SettingsScope $scope): void
    {
        $this->cache($scope)->forget($this->groupCacheKey($scope));
    }

    /**
     * Build aggregated group cache key for group array queries.
     */
    protected function groupCacheKey(SettingsScope $scope): string
    {
        return $scope->hasGroup() ? 'group_all:' . $scope->group() : 'group_all:root';
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