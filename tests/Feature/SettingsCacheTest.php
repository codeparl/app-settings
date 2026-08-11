<?php

use Illuminate\Support\Facades\DB;
use SchoolPalm\AppSettings\Facades\AppSettings;
use SchoolPalm\AppSettings\Models\Setting;

beforeEach(function () {
    // Reset database query log before each test
    DB::enableQueryLog();
    DB::flushQueryLog();
});

it('caches single key reads on subsequent gets', function () {
    // Write directly to DB to bypass put() cache warming
    Setting::create([
        'group' => 'notifications',
        'key' => 'email_enabled',
        'value' => true,
    ]);

    // 1. Cold Read: Triggers database query and populates cache
    DB::flushQueryLog();
    $firstRead = AppSettings::group('notifications')->get('email_enabled');

    expect($firstRead)->toBeTrue();
    expect(DB::getQueryLog())->not->toBeEmpty();

    // 2. Warm Read: Served directly from cache (0 DB queries)
    DB::flushQueryLog();
    $secondRead = AppSettings::group('notifications')->get('email_enabled');

    expect($secondRead)->toBeTrue();
    expect(DB::getQueryLog())->toBeEmpty();
});
it('caches nested group array structure on all calls', function () {
    AppSettings::group('reports.card')->put('show_photo', true);
    AppSettings::group('reports.card')->put('layout.columns', 2);

    // Initial call caches the group payload
    DB::flushQueryLog();
    $firstGroupResult = AppSettings::group('reports.card')->all();

    expect($firstGroupResult)->toBe([
        'show_photo' => true,
        'layout' => [
            'columns' => 2,
        ],
    ]);
    expect(DB::getQueryLog())->not->toBeEmpty();

    // Subsequent call uses cached group array
    DB::flushQueryLog();
    $secondGroupResult = AppSettings::group('reports.card')->all();

    expect($secondGroupResult)->toBe($firstGroupResult);
    expect(DB::getQueryLog())->toBeEmpty();
});

it('invalidates group cache when a new key is added or updated', function () {
    AppSettings::group('reports.card')->put('show_photo', true);

    // Warm the group cache
    $initialGroup = AppSettings::group('reports.card')->all();
    expect($initialGroup)->toBe(['show_photo' => true]);

    // Mutate state within group
    AppSettings::group('reports.card')->put('show_logo', false);

    // Group cache should invalidate and fetch updated structure
    $updatedGroup = AppSettings::group('reports.card')->all();

    expect($updatedGroup)->toBe([
        'show_photo' => true,
        'show_logo' => false,
    ]);
});

it('invalidates cache when a key is forgotten', function () {
    AppSettings::group('auth')->put('max_attempts', 5);

    // Warm cache
    expect(AppSettings::group('auth')->get('max_attempts'))->toBe(5);

    // Forget key
    AppSettings::group('auth')->forget('max_attempts');

    // Query database directly to ensure key is missing
    DB::flushQueryLog();
    $result = AppSettings::group('auth')->get('max_attempts', default: 3);

    expect($result)->toBe(3);
    expect(DB::getQueryLog())->not->toBeEmpty();
});

it('flushes both database records and cached keys for a scope', function () {
    AppSettings::group('billing')->put('currency', 'USD');
    AppSettings::group('billing')->put('tax.rate', 18);

    // Warm caches
    AppSettings::group('billing')->get('currency');
    AppSettings::group('billing')->all();

    // Flush entire scope
    AppSettings::group('billing')->flush();

    // Database should be empty for scope
    expect(AppSettings::group('billing')->all())->toBeEmpty();

    // Individual cache checks should miss and return default
    DB::flushQueryLog();
    expect(AppSettings::group('billing')->get('currency', 'EUR'))->toBe('EUR');
    expect(DB::getQueryLog())->not->toBeEmpty();
});
