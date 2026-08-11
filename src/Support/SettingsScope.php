<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Support;

/**
 * Class SettingsScope
 *
 * Represents the scope in which a setting operation is performed.
 *
 * A scope defines optional isolation boundaries for settings:
 *
 * - Database connection
 * - Context (tenant, school, user, branch, etc.)
 * - Group
 *
 * Examples:
 *
 * Global setting:
 *
 * <code>
 * new SettingsScope();
 * </code>
 *
 * School setting:
 *
 * <code>
 * new SettingsScope(
 *     contextType: 'school',
 *     contextId: 15
 * );
 * </code>
 *
 * Grouped school setting:
 *
 * <code>
 * new SettingsScope(
 *     connection: 'tenant',
 *     contextType: 'school',
 *     contextId: 15,
 *     group: 'report_cards'
 * );
 * </code>
 *
 * This class does not resolve database connections or contexts.
 * It only stores the requested scope.
 */
final class SettingsScope
{
    /**
     * Create a new settings scope.
     *
     * @param string|null $connection
     *        Database connection name.
     *
     * @param string|null $contextType
     *        Context namespace.
     *
     *        Examples:
     *        tenant, school, branch, user
     *
     * @param string|int|null $contextId
     *        Context identifier.
     *
     * @param string|null $group
     *        Optional settings group.
     *
     *        Examples:
     *        report_cards, grading, sms
     */
    /**
     * Create a new settings scope.
     *
     * @param string|null $connection
     * @param string|null $contextType
     * @param string|int|null $contextId
     * @param string|null $group
     * @param mixed|null $cacheContext
     */
    public function __construct(
        protected ?string $connection = null,

        protected ?string $contextType = null,

        protected string|int|null $contextId = null,

        protected ?string $group = null,

        protected mixed $cacheContext = null,
    ) {}

    /**
     * Set cache context.
     *
     * Cache context is independent from application settings context.
     *
     * It is passed to cache-store consumers when caching values.
     *
     * Example:
     *
     * <code>
     * $scope->withCacheContext([
     *     'tenant' => 10,
     * ]);
     * </code>
     *
     * @param mixed $context
     *
     * @return static
     */
    public function withCacheContext(
        mixed $context
    ): static {

        return new static(

            connection: $this->connection,

            contextType: $this->contextType,

            contextId: $this->contextId,

            group: $this->group,

            cacheContext: $context,

        );
    }


    /**
     * Get cache context.
     *
     * May return null when no cache isolation
     * is required.
     *
     * @return mixed|null
     */
    public function cacheContext(): mixed
    {
        return $this->cacheContext;
    }

    /**
     * Get database connection name.
     *
     * @return string|null
     */
    public function connection(): ?string
    {
        return $this->connection;
    }


    /**
     * Get context type.
     *
     * @return string|null
     */
    public function contextType(): ?string
    {
        return $this->contextType;
    }


    /**
     * Get context identifier.
     *
     * @return string|int|null
     */
    public function contextId(): string|int|null
    {
        return $this->contextId;
    }


    /**
     * Get settings group.
     *
     * @return string|null
     */
    public function group(): ?string
    {
        return $this->group;
    }


    /**
     * Determine whether this scope has a context.
     *
     * @return bool
     */
    public function hasContext(): bool
    {
        return $this->contextType !== null;
    }


    /**
     * Determine whether this scope belongs to a group.
     *
     * @return bool
     */
    public function hasGroup(): bool
    {
        return $this->group !== null;
    }


    /**
     * Create a new scope with a different connection.
     *
     * Keeps the current scope values unchanged.
     *
     * @param string|null $connection
     *
     * @return static
     */
    public function withConnection(
        ?string $connection
    ): static {

        return new static(

            connection: $connection,

            contextType: $this->contextType,

            contextId: $this->contextId,

            group: $this->group,

            cacheContext: $this->cacheContext,

        );
    }


    /**
     * Create a new scope with a context.
     *
     * @param string $type
     * @param string|int|null $id
     *
     * @return static
     */
    public function withContext(
        string $type,
        string|int|null $id = null
    ): static {

        return new static(
            connection: $this->connection,
            contextType: $type,
            contextId: $id,
            group: $this->group,
            cacheContext: $this->cacheContext
        );
    }


    /**
     * Create a new scope with a group.
     *
     * @param string|null $group
     *
     * @return static
     */
    public function withGroup(
        ?string $group
    ): static {

        return new static(
            connection: $this->connection,
            contextType: $this->contextType,
            contextId: $this->contextId,
            group: $group,
            cacheContext: $this->cacheContext
        );
    }


    /**
     * Convert scope information into an array.
     *
     * Useful for debugging and cache key generation.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'connection'   => $this->connection,
            'context_type' => $this->contextType,
            'context_id'   => $this->contextId,
            'group'        => $this->group,
            'cacheContext' => $this->cacheContext,
        ];
    }

    /**
     * Set or mutate the group name for this scope.
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }
}
