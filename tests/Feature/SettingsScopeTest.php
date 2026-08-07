<?php

use SchoolPalm\AppSettings\Facades\AppSettings;
use SchoolPalm\AppSettings\Models\Setting;
use SchoolPalm\AppSettings\Support\SettingsScope;
use SchoolPalm\CacheStore\Facades\CacheStore;

it('uses default cache when cache context is not provided', function () {

    $scope = new SettingsScope(
        contextType: 'school',
        contextId: 1
    );


    $resolver = app(
        \SchoolPalm\AppSettings\Resolvers\SettingsResolver::class
    );


    $resolver->put(
        $scope,
        'school.name',
        'Emma High'
    );


    expect(
        $resolver->get(
            $scope,
            'school.name'
        )
    )
        ->toBe('Emma High');
});

it('uses cache context when provided', function () {

    $scope = new SettingsScope(
        contextType: 'school',
        contextId: 1,
        cacheContext: [
            'tenant_abc',
            'school_123'
        ]
    );


    $resolver = app(
        \SchoolPalm\AppSettings\Resolvers\SettingsResolver::class
    );


    $resolver->put(
        $scope,
        'school.name',
        'Emma High'
    );


    expect(
        $resolver->get(
            $scope,
            'school.name'
        )
    )
        ->toBe('Emma High');
});


it('isolates settings by context', function () {


    AppSettings::context(
        'school',
        1
    )
        ->put(
            'name',
            'School One'
        );

    AppSettings::context(
        'school',
        2
    )
        ->put(
            'name',
            'School two'
        );

    expect(

        AppSettings::context(
            'school',
            1
        )
            ->get('name')

    )
        ->toBe(
            'School One'
        );
});




it('stores settings in cache after first retrieval', function () {


    AppSettings::put(
        'school.name',
        'Emma High'
    );


    expect(
        AppSettings::get('school.name')
    )
        ->toBe(
            'Emma High'
        );


    expect(
        AppSettings::get('school.name')
    )
        ->toBe(
            'Emma High'
        );
});

it('isolates settings by group within the same context', function () {
    $context = AppSettings::context('school', 1);

    $context->group('message_delivery.email')->put('provider', 'smtp');
    $context->group('message_delivery.sms')->put('provider', 'meta');

    expect($context->group('message_delivery.email')->get('provider'))
        ->toBe('smtp');

    expect($context->group('message_delivery.sms')->get('provider'))
        ->toBe('meta');
});

it('isolates grouped settings across different contexts', function () {
    AppSettings::context('school', 1)
        ->group('message_delivery.email')
        ->put('config', ['host' => 'smtp.school1.com']);

    AppSettings::context('school', 2)
        ->group('message_delivery.email')
        ->put('config', ['host' => 'smtp.school2.com']);

    expect(AppSettings::context('school', 1)->group('message_delivery.email')->get('config'))
        ->toBe(['host' => 'smtp.school1.com']);

    expect(AppSettings::context('school', 2)->group('message_delivery.email')->get('config'))
        ->toBe(['host' => 'smtp.school2.com']);
});

it('prevents group configuration bleed when setting complex arrays', function () {
    $emailConfig = [
        'phone_number_id' => '111111111111111',
        'access_token' => 'token_email',
        'business_id' => 'biz_email',
    ];

    $whatsappConfig = [
        'phone_number_id' => '222222222222222',
        'access_token' => 'token_whatsapp',
        'business_id' => 'biz_whatsapp',
    ];

    // Seed email first, then whatsapp
    AppSettings::group('message_delivery.email')->put('config', $emailConfig);
    AppSettings::group('message_delivery.whatsapp')->put('config', $whatsappConfig);

    // Verify email config wasn't overwritten by whatsapp config
    expect(AppSettings::group('message_delivery.email')->get('config'))
        ->toBe($emailConfig)
        ->and(AppSettings::group('message_delivery.whatsapp')->get('config'))
        ->toBe($whatsappConfig);
});

it('supports settings under specific groups', function () {
    // 1. Store settings under distinct groups
    AppSettings::group('report_cards')->put('template', 'modern_v2');
    AppSettings::group('sms_gateway')->put('provider', 'twilio');

    // 2. Assert group isolation
    expect(AppSettings::group('report_cards')->get('template'))
        ->toBe('modern_v2');

    expect(AppSettings::group('sms_gateway')->get('provider'))
        ->toBe('twilio');

    // Key without group context should not exist
    expect(AppSettings::has('template'))->toBeFalse();
});

it('persists group scope correctly in database settings table', function () {
    AppSettings::context('school', 10)
        ->group('grading')
        ->put('pass_mark', 50);

    $this->assertDatabaseHas('settings', [
        'context_type' => 'school',
        'context_id'   => '10',
        'group'        => 'grading',
        'key'          => 'pass_mark',
    ]);
});

it('flushes settings from cache and database for a context', function () {
    AppSettings::context('school', 1)->put('name', 'School One');

    // Warm the cache so the value is present in both cache and DB.
    expect(AppSettings::context('school', 1)->get('name'))
        ->toBe('School One');

    AppSettings::context('school', 1)->flush();

    // Value must be gone from both cache and DB.
    expect(AppSettings::context('school', 1)->get('name'))
        ->toBeNull();

    $this->assertDatabaseMissing('settings', [
        'context_type' => 'school',
        'context_id'   => '1',
        'key'          => 'name',
    ]);
});

it('flush only affects the targeted context', function () {
    AppSettings::context('school', 1)->put('name', 'School One');
    AppSettings::context('school', 2)->put('name', 'School Two');

    AppSettings::context('school', 1)->flush();

    expect(AppSettings::context('school', 1)->get('name'))
        ->toBeNull();

    expect(AppSettings::context('school', 2)->get('name'))
        ->toBe('School Two');
});

it('flush only affects the targeted group within a context', function () {
    $context = AppSettings::context('school', 1);

    $context->group('message_delivery.email')->put('provider', 'smtp');
    $context->group('message_delivery.sms')->put('provider', 'meta');

    // Flush only the email group.
    $context->group('message_delivery.email')->flush();

    expect($context->group('message_delivery.email')->get('provider'))
        ->toBeNull();

    expect($context->group('message_delivery.sms')->get('provider'))
        ->toBe('meta');
});
