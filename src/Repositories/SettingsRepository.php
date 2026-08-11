<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use SchoolPalm\AppSettings\Models\Setting;
use SchoolPalm\AppSettings\Support\SettingsScope;

/**
 * Class SettingsRepository
 *
 * Handles persistence operations for application settings.
 */
class SettingsRepository
{
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

            $group = $scope->group();

            if ($group !== null && str_contains($group, '.')) {
                $lastDot = strrpos($group, '.');
                $parentGroup = substr($group, 0, $lastDot);
                $leafKey = substr($group, $lastDot + 1);

                $parentScope = clone $scope;
                $parentScope->setGroup($parentGroup);

                return $this->get($parentScope, $leafKey, $default);
            }

            return $default;
        }

        $all = $this->all($scope);

        if (Arr::has($all, $key)) {
            return Arr::get($all, $key);
        }

        return $default;
    }

    public function put(
        SettingsScope $scope,
        string $key,
        mixed $value
    ): void {
        if (is_array($value) && Arr::isAssoc($value)) {
            foreach (Arr::dot($value, $key . '.') as $dotKey => $dotValue) {
                $this->putSingleKey($scope, $dotKey, $dotValue);
            }

            return;
        }

        $this->putSingleKey($scope, $key, $value);
    }

    protected function putSingleKey(
        SettingsScope $scope,
        string $key,
        mixed $value
    ): void {
        $conditions = [
            'key' => $key,
        ];

        if ($scope->contextType() !== null) {
            $conditions['context_type'] = $scope->contextType();
            $conditions['context_id'] = $scope->contextId();
        } else {
            $conditions['context_type'] = null;
            $conditions['context_id'] = null;
        }

        $conditions['group'] = $scope->group();

        $this->exactQuery($scope)
            ->updateOrCreate(
                $conditions,
                [
                    'value' => $value,
                ]
            );
    }

    public function has(
        SettingsScope $scope,
        string $key
    ): bool {
        $existsExact = $this->exactQuery($scope)
            ->where('key', $key)
            ->exists();

        if ($existsExact) {
            return true;
        }

        return Arr::has($this->all($scope), $key);
    }

    public function forget(
        SettingsScope $scope,
        string $key
    ): void {
        // 1. Delete exact key match
        $this->exactQuery($scope)
            ->where('key', $key)
            ->delete();

        // 2. Delete sub-keys if key was a dot-notation parent path
        $this->exactQuery($scope)
            ->where('key', 'LIKE', $key . '.%')
            ->delete();
    }

    public function flush(
        SettingsScope $scope
    ): void {
        $this->query($scope, includeAllGroups: true)
            ->delete();
    }

    public function all(
        SettingsScope $scope
    ): array {
        if ($scope->group() !== null) {
            return $this->group($scope);
        }

        $records = $this->query($scope, includeAllGroups: true)->get();
        $results = [];

        foreach ($records as $setting) {
            $path = $setting->group
                ? "{$setting->group}.{$setting->key}"
                : $setting->key;

            Arr::set($results, $path, $setting->value);
        }

        return $results;
    }

    public function group(
        SettingsScope $scope
    ): array {
        $targetGroup = $scope->group();

        if ($targetGroup === null) {
            return $this->all($scope);
        }

        $records = $this->query($scope)->get();
        $results = [];

        foreach ($records as $setting) {
            if ($setting->group === $targetGroup) {
                Arr::set($results, $setting->key, $setting->value);
            } else {
                $relativeGroup = ltrim(substr($setting->group, strlen($targetGroup)), '.');
                $fullPath = "{$relativeGroup}.{$setting->key}";

                Arr::set($results, $fullPath, $setting->value);
            }
        }

        return $results;
    }

    protected function query(
        SettingsScope $scope,
        bool $includeAllGroups = false
    ): Builder {
        $model = new Setting();

        if ($scope->connection()) {
            $model->setConnection($scope->connection());
        }

        $query = $model->newQuery();

        if ($scope->contextType()) {
            $query->where('context_type', $scope->contextType());
            $query->where('context_id', $scope->contextId());
        } else {
            $query->whereNull('context_type');
            $query->whereNull('context_id');
        }

        if ($scope->group()) {
            $group = $scope->group();

            $query->where(function (Builder $q) use ($group) {
                $q->where('group', $group)
                    ->orWhere('group', 'LIKE', $group . '.%');
            });
        } elseif (! $includeAllGroups) {
            $query->whereNull('group');
        }

        return $query;
    }

    protected function exactQuery(
        SettingsScope $scope
    ): Builder {
        $model = new Setting();

        if ($scope->connection()) {
            $model->setConnection($scope->connection());
        }

        $query = $model->newQuery();

        if ($scope->contextType()) {
            $query->where('context_type', $scope->contextType());
            $query->where('context_id', $scope->contextId());
        } else {
            $query->whereNull('context_type');
            $query->whereNull('context_id');
        }

        if ($scope->group()) {
            $query->where('group', $scope->group());
        } else {
            $query->whereNull('group');
        }

        return $query;
    }
}
