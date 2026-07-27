<?php

declare(strict_types=1);

namespace SchoolPalm\AppSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * AppSettings Facade
 *
 * Provides a developer-friendly API for managing application settings.
 *
 * Supports:
 *
 * - Dynamic database connections
 * - Context scoped settings
 * - Grouped settings
 * - Optional cache context
 * - Cache-first resolution
 *
 * Example:
 *
 * <code>
 * AppSettings::connect('tenant')
 *     ->context('school', 10)
 *     ->group('report_cards')
 *     ->get(
 *         'show_photo',
 *         true
 *     );
 * </code>
 *
 * @method static \SchoolPalm\AppSettings\Managers\SettingsManager connect(?string $connection)
 *
 * @method static \SchoolPalm\AppSettings\Managers\SettingsManager context(string $type, string|int|null $identifier = null)
 *
 * @method static \SchoolPalm\AppSettings\Managers\SettingsManager group(?string $group)
 *
 * @method static \SchoolPalm\AppSettings\Managers\SettingsManager withCacheContext(?string $tenantId, ?string $schoolId)
 *
 * @method static mixed get(string $key, mixed $default = null)
 *
 * @method static \SchoolPalm\AppSettings\Managers\SettingsManager put(string $key, mixed $value)
 *
 * @method static bool has(string $key)
 *
 * @method static \SchoolPalm\AppSettings\Managers\SettingsManager forget(string $key)
 *
 * @method static array all()
 *
 * @method static void flush()
 *
 * @see \SchoolPalm\AppSettings\Managers\SettingsManager
 */
final class AppSettings extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'app-settings.manager';
    }
}
