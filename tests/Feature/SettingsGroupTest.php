<?php

use Illuminate\Support\Facades\DB;
use SchoolPalm\AppSettings\Facades\AppSettings;

it('stores settings inside a group', function () {
    AppSettings::group('report_cards')
        ->put('show_photo', true);

    expect(
        AppSettings::group('report_cards')
            ->get('show_photo')
    )->toBeTrue();
});

it('retrieves nested group settings as multidimensional arrays matching config behavior', function () {
    // 1. Set values at root group level
    AppSettings::group('message_delivery.in_app')
        ->put('default_provider', 'database-notifications');

    // 2. Set values at sub-group level
    AppSettings::group('message_delivery.in_app.database-notifications')
        ->put('enabled', true);

    // 3. Set deep child values using dot-notation keys
    AppSettings::group('message_delivery.in_app.database-notifications')
        ->put('channels.email', false);

    // Fetch parent group structure
    $groupData = AppSettings::group('message_delivery.in_app')->all();

    expect($groupData)->toBe([
        'default_provider' => 'database-notifications',
        'database-notifications' => [
            'enabled' => true,
            'channels' => [
                'email' => false,
            ],
        ],
    ]);
});

it('maintains relative scoping when fetching sub-groups directly', function () {
    AppSettings::group('message_delivery.in_app.database-notifications')
        ->put('enabled', true);

    AppSettings::group('message_delivery.in_app.database-notifications')
        ->put('retry.limit', 3);

    $subGroupData = AppSettings::group('message_delivery.in_app.database-notifications')->all();

    expect($subGroupData)->toBe([
        'enabled' => true,
        'retry' => [
            'limit' => 3,
        ],
    ]);
});

it('allows parameterless get() calls on group scopes', function () {
    AppSettings::group('message_delivery.email.laravel-mail')->put('enabled', true);

    // Retrieve entire array via group scope
    $allInGroup = AppSettings::group('message_delivery.email.laravel-mail')->get();
    expect($allInGroup)->toBe(['enabled' => true]);

    // Retrieve scalar directly via group scope
    $value = AppSettings::group('message_delivery.email.laravel-mail.enabled')->get();
    expect($value)->toBeTrue();
});
