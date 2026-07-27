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




// it('stores settings in cache after first retrieval', function () {


//     AppSettings::put(
//         'school.name',
//         'Emma High'
//     );


//     expect(
//         AppSettings::get('school.name')
//     )
//         ->toBe(
//             'Emma High'
//         );


//     expect(
//         AppSettings::get('school.name')
//     )
//         ->toBe(
//             'Emma High'
//         );
// });
