<?php

use SchoolPalm\AppSettings\Facades\AppSettings;


it('stores and retrieves a setting', function () {

    AppSettings::put(
        'school.name',
        'Emma High School'
    );


    expect(
        AppSettings::get('school.name')
    )
        ->toBe(
            'Emma High School'
        );
});



it('returns default value when setting does not exist', function () {


    expect(
        AppSettings::get(
            'school.motto',
            'Excellence'
        )
    )
        ->toBe(
            'Excellence'
        );
});



it('checks if a setting exists', function () {

    AppSettings::put(
        'timezone',
        'Africa/Kampala'
    );


    expect(
        AppSettings::has('timezone')
    )
        ->toBeTrue();
});
