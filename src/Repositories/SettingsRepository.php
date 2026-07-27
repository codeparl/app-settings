<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Repositories;

use Illuminate\Database\Eloquent\Builder;
use SchoolPalm\AppSettings\Models\Setting;
use SchoolPalm\AppSettings\Support\SettingsScope;

/**
 * Class SettingsRepository
 *
 * Handles persistence operations for application settings.
 *
 * Responsibilities:
 *
 * - Reading settings from storage
 * - Writing settings to storage
 * - Removing settings
 * - Querying scoped settings
 *
 * The repository does not resolve:
 *
 * - Tenants
 * - Schools
 * - Users
 * - Database connection rules
 *
 * Those responsibilities are provided through SettingsScope
 * by the consuming application.
 */
class SettingsRepository
{

    /**
     * Retrieve a setting value.
     *
     * Example:
     *
     * <code>
     * $repository->get(
     *     $scope,
     *     'report_cards.show_photo'
     * );
     * </code>
     *
     * @param SettingsScope $scope Current settings scope.
     * @param string $key Setting key.
     * @param mixed $default Default value.
     *
     * @return mixed
     */
    public function get(
        SettingsScope $scope,
        string $key,
        mixed $default = null
    ): mixed {

        $setting = $this->query($scope)
            ->where('key', $key)
            ->first();

        //    dd($setting);

        if (!$setting) {

            return $default;
        }


        return $setting->value;
    }



    /**
     * Store a setting value.
     *
     * Creates the setting if it does not exist,
     * otherwise updates the existing value.
     *
     * The scope query already applies:
     *
     * - Connection
     * - Context
     * - Group
     *
     * ensuring values remain isolated.
     *
     * @param SettingsScope $scope
     * @param string $key
     * @param mixed $value
     *
     * @return void
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

            $conditions['context_type'] =
                $scope->contextType();

            $conditions['context_id'] =
                $scope->contextId();
        } else {

            $conditions['context_type'] = null;

            $conditions['context_id'] = null;
        }


        $conditions['group'] =
            $scope->group();


        $this->query($scope)
            ->updateOrCreate(
                $conditions,
                [
                    'value' => $value,
                ]
            );
    }



    /**
     * Determine whether a setting exists.
     *
     * @param SettingsScope $scope
     * @param string $key
     *
     * @return bool
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
     *
     * @param SettingsScope $scope
     * @param string $key
     *
     * @return void
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
     * Retrieve all settings in current scope.
     *
     * Returns:
     *
     * [
     *     "school.name" => "Emma High",
     *     "theme.color" => "blue"
     * ]
     *
     * @param SettingsScope $scope
     *
     * @return array<string,mixed>
     */
    public function all(
        SettingsScope $scope
    ): array {

        return $this->query($scope)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }



    /**
     * Remove all settings in current scope.
     *
     * @param SettingsScope $scope
     *
     * @return void
     */
    public function flush(
        SettingsScope $scope
    ): void {

        $this->query($scope)
            ->delete();
    }



    /**
     * Retrieve settings belonging to the current group.
     *
     * The group is already provided by SettingsScope.
     *
     * Example:
     *
     * <code>
     * $scope = $scope->withGroup('report_cards');
     *
     * $repository->all($scope);
     * </code>
     *
     * @param SettingsScope $scope
     *
     * @return array<string,mixed>
     */
    public function group(
        SettingsScope $scope
    ): array {

        return $this->all($scope);
    }



    /**
     * Create scoped settings query.
     *
     * Applies:
     *
     * - Dynamic database connection
     * - Context isolation
     * - Group isolation
     *
     * Global settings:
     *
     * context_type = NULL
     * context_id   = NULL
     *
     * Scoped settings:
     *
     * context_type = school
     * context_id   = 10
     *
     *
     * @param SettingsScope $scope
     *
     * @return Builder
     */
    protected function query(
        SettingsScope $scope
    ): Builder {

        $model = new Setting();


        if ($scope->connection()) {

            $model->setConnection(
                $scope->connection()
            );
        }


        $query = $model->newQuery();



        /**
         * Apply settings context.
         *
         * If no context exists,
         * explicitly query global settings.
         */
        if ($scope->contextType()) {

            $query->where(
                'context_type',
                $scope->contextType()
            );


            $query->where(
                'context_id',
                $scope->contextId()
            );
        } else {

            $query->whereNull(
                'context_type'
            );


            $query->whereNull(
                'context_id'
            );
        }



        /**
         * Apply settings group.
         */
        if ($scope->group()) {

            $query->where(
                'group',
                $scope->group()
            );
        }


        return $query;
    }
}
