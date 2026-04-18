<?php

test('home page responds with 200 for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('home page uses the marketing layout', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee(config('app.name'), escape: false)
        ->assertSee('Skills that build empires.', escape: false)
        ->assertSee('Sign in', escape: false)
        ->assertSee('Register', escape: false);
});
