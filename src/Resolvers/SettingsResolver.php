<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Resolvers;

use Illuminate\Support\Arr;
use SchoolPalm\AppSettings\Repositories\SettingsRepository;
use SchoolPalm\AppSettings\Support\SettingsScope;
use SchoolPalm\CacheStore\Facades\CacheStore;

/**
 * Class SettingsResolver
 *
 * Resolves settings using a cache-first strategy driven by unified group cache invalidation.
 */
class SettingsResolver
{
    public function __construct(
        protected SettingsRepository $repository
    ) {}

    /**
     * Retrieve a setting value using cache or resolution fallback.
     */
    public function get(
        SettingsScope $scope,
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null || $key === '') {
            $all = $this->all($scope);

            if (!empty($all)) {
                return $all;
            }

            // Fall back to repository to attempt resolving trailing leaf segment as a key
            return $this->repository->get($scope, null, $default);
        }

        return $this->cache($scope)
            ->remember(
                $this->cacheKey($scope, $key),
                config('app-settings.cache.ttl'),
                function () use ($scope, $key, $default) {
                    return $this->repository->get(
                        $scope,
                        $key,
                        $default
                    );
                }
            );
    }

    /**
     * Store a setting value in database and update group hierarchy cache.
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

        $this->cache($scope)->forget($this->cacheKey($scope, $key));
        $this->invalidateGroupHierarchyCache($scope);
    }

    /**
     * Remove a setting or sub-branch from database and update group hierarchy cache.
     */
    public function forget(
        SettingsScope $scope,
        string $key
    ): void {
        $this->repository->forget(
            $scope,
            $key
        );

        $this->cache($scope)->forget($this->cacheKey($scope, $key));
        $this->invalidateGroupHierarchyCache($scope);
    }

    /**
     * Determine if setting exists in current group scope.
     */
    public function has(
        SettingsScope $scope,
        string $key
    ): bool {
        return Arr::has($this->all($scope), $key);
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
                $this->groupCacheKey($scope->group()),
                config('app-settings.cache.ttl'),
                function () use ($scope) {
                    return $this->repository->all($scope);
                }
            );
    }

    /**
     * Remove all settings in current scope and invalidate group hierarchy cache.
     */
    public function flush(
        SettingsScope $scope
    ): void {
        $this->repository->flush($scope);

        $cache = $this->cache($scope);

        if (method_exists($cache, 'flush')) {
            $cache->flush();
        } else {
            $this->invalidateGroupHierarchyCache($scope);
        }
    }

    /**
     * Bubble up and invalidate all parent group caches when a sub-group changes.
     */
    protected function invalidateGroupHierarchyCache(SettingsScope $scope): void
    {
        $cache = $this->cache($scope);
        $group = $scope->group();

        while ($group !== null && $group !== '') {
            $cache->forget($this->groupCacheKey($group));

            $lastDotPosition = strrpos($group, '.');
            $group = $lastDotPosition !== false ? substr($group, 0, $lastDotPosition) : null;
        }

        // Always invalidate the root group cache
        $cache->forget($this->groupCacheKey(null));
    }

    /**
     * Build aggregated group cache key for group array queries.
     */
    protected function groupCacheKey(?string $group): string
    {
        return $group !== null && $group !== '' ? 'group_all:' . $group : 'group_all:root';
    }

    /**
     * Resolve cache instance for scope.
     */
    protected function cache(
        SettingsScope $scope
    ): mixed {
        if ($scope->cacheContext() !== null) {
            return CacheStore::forContext(
                ...$scope->cacheContext()
            );
        }

        if ($scope->contextType() !== null) {
            return CacheStore::forContext(
                $scope->contextType(),
                (string) $scope->contextId()
            );
        }

        return CacheStore::getFacadeRoot();
    }

    /**
     * Build a group-aware cache key.
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
