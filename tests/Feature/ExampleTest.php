<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('landing page presents SatuID branding', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('SatuID')
        ->assertSee(route('login'), false);
});
