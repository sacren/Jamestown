<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

dataset('guest auth views', [
    'login' => fn () => route('login'),
    'register' => fn () => route('register'),
    'forgot password' => fn () => route('password.request'),
    'reset password' => fn () => route('password.reset', ['token' => 'abc123']),
]);

test('guest auth view links back to home', function (Closure $url) {
    $this->get($url())
        ->assertOk()
        ->assertSee(__('Back to home'))
        ->assertSee('href="'.route('home').'"', false);
})->with('guest auth views');

test('two factor challenge view links back to home', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->get(route('two-factor.login'))
        ->assertOk()
        ->assertSee(__('Back to home'))
        ->assertSee('href="'.route('home').'"', false);
});

test('email verification view links back to home', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee(__('Back to home'))
        ->assertSee('href="'.route('home').'"', false);
});

test('confirm password view links back to home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(__('Back to home'))
        ->assertSee('href="'.route('home').'"', false);
});
