<?php

use SchoolPalm\AppSettings\Facades\AppSettings;


it('stores settings inside a group', function () {


    AppSettings::group('report_cards')
        ->put(
            'show_photo',
            true
        );


    expect(
        AppSettings::group('report_cards')
            ->get(
                'show_photo'
            )
    )
        ->toBeTrue();
});
