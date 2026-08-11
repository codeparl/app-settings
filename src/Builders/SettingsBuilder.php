<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Builders;

use SchoolPalm\AppSettings\Resolvers\SettingsResolver;
use SchoolPalm\AppSettings\Support\SettingsScope;

/**
 * Class SettingsBuilder
 *
 * Provides a fluent API for interacting with application settings.
 *
 * The builder manages the current operation scope and delegates
 * resolution operations to SettingsResolver.
 *
 * Supported scopes:
 *
 * - Database connection
 * - Context (tenant, school, user, branch, etc.)
 * - Group
 */
class SettingsBuilder
{

    /**
     * Create a new settings builder.
     *
     * @param SettingsResolver $resolver
     * @param SettingsScope|null $scope
     */
    public function __construct(
        protected SettingsResolver $resolver,
        protected ?SettingsScope $scope = null
    ) {

        $this->scope =
            $scope ?? new SettingsScope();
    }



    /**
     * Set database connection.
     *
     * @param string|null $connection
     *
     * @return static
     */
    public function connection(
        ?string $connection
    ): static {

        $this->scope =
            $this->scope->withConnection(
                $connection
            );

        return $this;
    }



    /**
     * Set settings context.
     *
     * Example:
     *
     * ->context('school', 15)
     *
     * @param string $type
     * @param string|int|null $identifier
     *
     * @return static
     */
    public function context(
        string $type,
        string|int|null $identifier = null
    ): static {

        $this->scope =
            $this->scope->withContext(
                $type,
                $identifier
            );

        return $this;
    }



    /**
     * Set settings group.
     *
     * Example:
     *
     * ->group('report_cards')
     *
     * @param string|null $group
     *
     * @return static
     */
    public function group(
        ?string $group
    ): static {

        $this->scope =
            $this->scope->withGroup(
                $group
            );

        return $this;
    }



    /**
     * Retrieve setting value.
     *
     * Resolution flow:
     *
     * Cache
     * Database
     * Default value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    /**
     * Retrieve a setting value or the entire group scope.
     *
     * @param string|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        return $this->resolver->get(
            $this->scope,
            $key,
            $default
        );
    }


    /**
     * Helper to retrieve a setting key or scoped group path directly.
     *
     * Examples:
     * $builder->settings('message_delivery.email.laravel-mail.enabled')
     * $builder->settings('message_delivery.email.laravel-mail.enabled', true)
     *
     * Proxies to:
     * $builder->group('message_delivery.email.laravel-mail.enabled')->get(default: $default)
     */
    public function settings(?string $path = null, mixed $default = null): mixed
    {
        if ($path === null || $path === '') {
            return $this->get(null, $default);
        }

        return $this->group($path)->get(null, $default);
    }

    /**
     * Alias for settings() to support singular/plural method call styles.
     */
    public function setting(?string $path = null, mixed $default = null): mixed
    {
        return $this->settings($path, $default);
    }

    /**
     * Store setting value.
     *
     * Database is updated first,
     * then cache is refreshed.
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

        $this->resolver->put(
            $this->scope,
            $key,
            $value
        );

        return $this;
    }



    /**
     * Store multiple settings.
     *
     * @param array<string,mixed> $settings
     *
     * @return static
     */
    public function putMany(
        array $settings
    ): static {

        foreach ($settings as $key => $value) {

            $this->put(
                $key,
                $value
            );
        }


        return $this;
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

        return $this->resolver->has(
            $this->scope,
            $key
        );
    }



    /**
     * Remove a setting.
     *
     * @param string $key
     *
     * @return static
     */
    public function forget(
        string $key
    ): static {

        $this->resolver->forget(
            $this->scope,
            $key
        );

        return $this;
    }



    /**
     * Retrieve all settings in current scope.
     *
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->resolver->all(
            $this->scope
        );
    }



    /**
     * Remove all settings in current scope.
     *
     * @return static
     */
    public function flush(): static
    {
        $this->resolver->flush(
            $this->scope
        );

        return $this;
    }



    /**
     * Get current settings scope.
     *
     * @return SettingsScope
     */
    public function scope(): SettingsScope
    {
        return $this->scope;
    }
}
