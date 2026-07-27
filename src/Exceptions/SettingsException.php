<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Exceptions;

use Exception;

/**
 * Class SettingsException
 *
 * Base exception for all app-settings package errors.
 */
class SettingsException extends Exception
{
    /**
     * Create a new exception for an invalid key.
     *
     * @param string $key The invalid key.
     *
     * @return static
     */
    public static function invalidKey(string $key): static
    {
        return new static(
            sprintf(
                'Invalid settings key provided: "%s". Keys must be non-empty strings.',
                $key
            )
        );
    }

    /**
     * Create a new exception for an unsupported value type.
     *
     * @param mixed $value The unsupported value.
     *
     * @return static
     */
    public static function unsupportedValue(mixed $value): static
    {
        return new static(
            sprintf(
                'Unsupported settings value type: "%s". Only string, int, float, bool, array, null, and serializable objects are supported.',
                get_debug_type($value)
            )
        );
    }

    /**
     * Create a new exception for a missing service implementation.
     *
     * @return static
     */
    public static function missingService(): static
    {
        return new static(
            'A SettingsService implementation must be registered before using AppSettings.'
        );
    }
}

