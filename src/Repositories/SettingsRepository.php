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
    /**
     * Retrieve a setting value.
     */
    public function get(
        SettingsScope $scope,
        string $key,
        mixed $default = null
    ): mixed {
        $setting = $this->query($scope)
            ->where('key', $key)
            ->first();

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Store a setting value.
     *
     * Exact group write (e.g. 'message_delivery.in_app')
     */
    public function put(
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

    /**
     * Determine whether a setting exists.
     */
    public function has(
        SettingsScope $scope,
        string $key
    ): bool {
        return $this->query($scope)
            ->where('key', $key)
            ->exists();
    }

    /**
     * Remove a setting.
     */
    public function forget(
        SettingsScope $scope,
        string $key
    ): void {
        $this->query($scope)
            ->where('key', $key)
            ->delete();
    }



    /**
     * Remove all settings in current scope.
     */
    public function flush(
        SettingsScope $scope
    ): void {
        $this->query($scope)
            ->delete();
    }

    /**
     * Retrieve all settings in current scope.
     *
     * @return array<string,mixed>
     */
    public function all(
        SettingsScope $scope
    ): array {
        if ($scope->group() !== null) {
            return $this->group($scope);
        }

        $records = $this->query($scope)->get();
        $results = [];

        foreach ($records as $setting) {
            Arr::set($results, $setting->key, $setting->value);
        }

        return $results;
    }

    /**
     * Retrieve settings belonging to the current group or sub-groups as a nested array.
     *
     * Expands dot-notation key paths into multidimensional associative arrays
     * matching standard Laravel config() behavior.
     *
     * @return array<string,mixed>
     */
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
                // Direct key on target group - use Arr::set to expand dot keys (e.g. 'retry.limit')
                Arr::set($results, $setting->key, $setting->value);
            } else {
                // Sub-group: strip parent group prefix to obtain relative path
                $relativeGroup = ltrim(substr($setting->group, strlen($targetGroup)), '.');
                $fullPath = "{$relativeGroup}.{$setting->key}";

                Arr::set($results, $fullPath, $setting->value);
            }
        }

        return $results;
    }
    /**
     * Create scoped settings query with support for hierarchical group matching.
     *
     * If scope group is 'message_delivery.in_app', this queries:
     * - group = 'message_delivery.in_app'
     * - group LIKE 'message_delivery.in_app.%'
     */
    protected function query(
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
            $group = $scope->group();

            $query->where(function (Builder $q) use ($group) {
                $q->where('group', $group)
                    ->orWhere('group', 'LIKE', $group . '.%');
            });
        } else {
            $query->whereNull('group');
        }

        return $query;
    }

    /**
     * Create exact scoped settings query for write/update operations.
     */
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
