<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Setting
 *
 * Represents an application setting record.
 *
 * The model is intentionally context aware but not
 * application aware.
 *
 * Context examples:
 *
 * - school:10
 * - branch:2
 * - user:50
 *
 * In SchoolPalm:
 *
 * - Database connection represents tenant isolation.
 * - context_id represents the school inside that tenant.
 *
 * Example:
 *
 * Tenant Database:
 *
 * settings
 *
 * context_type = school
 * context_id   = 5
 *
 * means:
 *
 * School ID 5 inside the current tenant.
 */
class Setting extends Model
{
    /**
     * Table name.
     */
    protected $table = 'settings';


    /**
     * Allow mass assignment.
     */
    protected $guarded = [];


    /**
     * Cast attributes.
     */
    protected $casts = [

        /**
         * Stored setting value.
         *
         * Values may contain:
         *
         * - string
         * - integer
         * - boolean
         * - array
         * - object
         */
        'value' => 'json',

    ];


    /**
     * The attributes that should be hidden.
     */
    protected $hidden = [];


    /**
     * Dynamically assign database connection.
     *
     * The package does not decide the connection.
     *
     * The SettingsRepository sets the connection
     * based on SettingsScope.
     *
     * Example:
     *
     * $setting->setConnection('tenant');
     *
     * @param string|null $connection
     *
     * @return $this
     */
    public function useConnection(
        ?string $connection
    ): static {

        if ($connection) {

            $this->setConnection(
                $connection
            );
        }

        return $this;
    }


    /**
     * Scope query to a specific context.
     *
     * Example:
     *
     * school:10
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @param string|int $id
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeContext(
        $query,
        string $type,
        string|int $id
    ) {

        return $query
            ->where(
                'context_type',
                $type
            )
            ->where(
                'context_id',
                $id
            );
    }


    /**
     * Scope query to a settings group.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroup(
        $query,
        string $group
    ) {

        return $query->where(
            'group',
            $group
        );
    }
}
