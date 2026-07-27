<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Managers;

use SchoolPalm\AppSettings\Builders\SettingsBuilder;
use SchoolPalm\AppSettings\Resolvers\SettingsResolver;
use SchoolPalm\AppSettings\Support\SettingsScope;

/**
 * Class SettingsManager
 *
 * Primary entry point for interacting with application settings.
 *
 * Provides a fluent API while delegating resolution
 * to SettingsResolver.
 *
 * Example:
 *
 * <code>
 * AppSettings::put(
 *     'school.name',
 *     'Emma High'
 * );
 *
 * AppSettings::group('report_cards')
 *     ->put(
 *         'show_photo',
 *         true
 *     );
 *
 * AppSettings::connection('tenant')
 *     ->context('school', 10)
 *     ->group('grading')
 *     ->put(
 *         'pass_mark',
 *         50
 *     );
 * </code>
 */
class SettingsManager
{

    /**
     * Create manager instance.
     *
     * @param SettingsResolver $resolver
     */
    public function __construct(
        protected SettingsResolver $resolver
    ) {}



    /**
     * Create a new settings builder.
     *
     * @return SettingsBuilder
     */
    protected function builder(): SettingsBuilder
    {
        return new SettingsBuilder(
            $this->resolver
        );
    }



    /**
     * Specify database connection.
     *
     * Example:
     *
     * <code>
     * AppSettings::connection('tenant')
     * </code>
     *
     * @param string|null $connection
     *
     * @return SettingsBuilder
     */
    public function connection(
        ?string $connection
    ): SettingsBuilder {

        return $this->builder()
            ->connection($connection);
    }



    /**
     * Specify settings context.
     *
     * Example:
     *
     * <code>
     * AppSettings::context(
     *     'school',
     *     10
     * );
     * </code>
     *
     * @param string $type
     * @param string|int|null $identifier
     *
     * @return SettingsBuilder
     */
    public function context(
        string $type,
        string|int|null $identifier = null
    ): SettingsBuilder {

        return $this->builder()
            ->context(
                $type,
                $identifier
            );
    }



    /**
     * Specify settings group.
     *
     * Example:
     *
     * <code>
     * AppSettings::group('report_cards')
     * </code>
     *
     * @param string $group
     *
     * @return SettingsBuilder
     */
    public function group(
        string $group
    ): SettingsBuilder {

        return $this->builder()
            ->group($group);
    }



    /**
     * Store setting.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return static
     */
    public function put(
        string $key,
        mixed $value
    ): static {

        $this->builder()
            ->put(
                $key,
                $value
            );

        return $this;
    }



    /**
     * Retrieve setting.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {

        return $this->builder()
            ->get(
                $key,
                $default
            );
    }



    /**
     * Determine whether setting exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(
        string $key
    ): bool {

        return $this->builder()
            ->has($key);
    }



    /**
     * Remove setting.
     *
     * @param string $key
     *
     * @return static
     */
    public function forget(
        string $key
    ): static {

        $this->builder()
            ->forget($key);

        return $this;
    }



    /**
     * Retrieve all settings.
     *
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->builder()
            ->all();
    }



    /**
     * Remove all settings.
     *
     * @return static
     */
    public function flush(): static
    {
        $this->builder()
            ->flush();

        return $this;
    }



    /**
     * Create builder from existing scope.
     *
     * @param SettingsScope $scope
     *
     * @return SettingsBuilder
     */
    public function withScope(
        SettingsScope $scope
    ): SettingsBuilder {

        return new SettingsBuilder(
            $this->resolver,
            $scope
        );
    }
}
