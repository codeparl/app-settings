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

it('retrieves nested sub-branches with get() and invalidates parent group cache on update', function () {
    // Seed nested settings within a sub-group
    AppSettings::group('message_delivery.email')->put('laravel-mail.driver', 'smtp');
    AppSettings::group('message_delivery.email')->put('laravel-mail.host', 'smtp.mailtrap.io');

    // 1. Warm parent group cache ('message_delivery')
    $parentGroup = AppSettings::group('message_delivery')->all();
    expect($parentGroup)->toBe([
        'email' => [
            'laravel-mail' => [
                'driver' => 'smtp',
                'host' => 'smtp.mailtrap.io',
            ],
        ],
    ]);

    // 2. Retrieve nested sub-branch using get()
    $mailConfig = AppSettings::group('message_delivery.email')->get('laravel-mail');
    expect($mailConfig)->toBe([
        'driver' => 'smtp',
        'host' => 'smtp.mailtrap.io',
    ]);

    // 3. Mutate child setting
    AppSettings::group('message_delivery.email')->put('laravel-mail.driver', 'ses');

    // 4. Parent group cache ('message_delivery') reflects updated value
    $updatedParentGroup = AppSettings::group('message_delivery')->all();
    expect($updatedParentGroup['email']['laravel-mail']['driver'])->toBe('ses');
});

it('correctly expands array payloads on put and resolves sub-branches on get, has, and all', function () {
    // 1. Put array payload into a deep group scope
    AppSettings::group('message_delivery.email.laravel-mail')->put('config', [
        'mailer' => 'mailpit',
        'timeout' => 30,
        'options' => [
            'encryption' => 'tls',
        ],
    ]);

    // 2. Retrieve relative sub-branch from parent group scope ('message_delivery.email')
    $laravelMailBranch = AppSettings::group('message_delivery.email')->get('laravel-mail');

    expect($laravelMailBranch)->toBe([
        'config' => [
            'mailer' => 'mailpit',
            'timeout' => 30,
            'options' => [
                'encryption' => 'tls',
            ],
        ],
    ]);

    // 3. Retrieve branch directly from child group scope ('message_delivery.email.laravel-mail')
    $configBranch = AppSettings::group('message_delivery.email.laravel-mail')->get('config');

    expect($configBranch)->toBe([
        'mailer' => 'mailpit',
        'timeout' => 30,
        'options' => [
            'encryption' => 'tls',
        ],
    ]);

    // 4. Dot-notation leaf lookup
    $mailer = AppSettings::group('message_delivery.email.laravel-mail')->get('config.mailer');
    expect($mailer)->toBe('mailpit');

    // 5. Test has() on parent group for a sub-branch
    expect(AppSettings::group('message_delivery.email')->has('laravel-mail'))->toBeTrue()
        ->and(AppSettings::group('message_delivery.email.laravel-mail')->has('config'))->toBeTrue()
        ->and(AppSettings::group('message_delivery.email.laravel-mail')->has('config.mailer'))->toBeTrue()
        ->and(AppSettings::group('message_delivery.email')->has('non_existent'))->toBeFalse();

    // 6. Test all() assembly from the root group
    $allDeliverySettings = AppSettings::group('message_delivery')->all();

    expect($allDeliverySettings)->toBe([
        'email' => [
            'laravel-mail' => [
                'config' => [
                    'mailer' => 'mailpit',
                    'timeout' => 30,
                    'options' => [
                        'encryption' => 'tls',
                    ],
                ],
            ],
        ],
    ]);
});

it('clears sub-branch cache properly on forget()', function () {
    AppSettings::group('message_delivery.email.laravel-mail')->put('config', [
        'mailer' => 'mailpit',
        'timeout' => 30,
    ]);

    // Warm up cache
    expect(AppSettings::group('message_delivery.email')->get('laravel-mail'))->toBeArray();

    // Forget sub-branch path
    AppSettings::group('message_delivery.email.laravel-mail')->forget('config');

    // Assert key is gone
    expect(AppSettings::group('message_delivery.email')->get('laravel-mail'))->toBeNull()
        ->and(AppSettings::group('message_delivery.email.laravel-mail')->has('config'))->toBeFalse();
});
