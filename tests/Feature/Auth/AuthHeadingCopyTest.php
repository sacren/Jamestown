<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('login view uses unified heading and action-oriented description', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('Welcome back'))
        ->assertSee(__('Sign in with your email and password to continue.'));
});

test('register view uses unified heading and action-oriented description', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee(__('Create your account'))
        ->assertSee(__('Tell us about yourself to get started.'));
});

test('forgot password view uses unified heading and action-oriented description', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee(__('Reset your password'))
        ->assertSee(__('Enter your email and we will send you a reset link.'));
});

test('reset password view uses unified heading and action-oriented description', function () {
    $this->get(route('password.reset', ['token' => 'abc123']))
        ->assertOk()
        ->assertSee(__('Set a new password'))
        ->assertSee(__('Choose a new password for your account.'));
});

test('verify email view uses unified heading and action-oriented description', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee(__('Check your email'))
        ->assertSee(__('We sent a verification link to your inbox. Click it to activate your account.'));
});

test('confirm password view uses unified heading and action-oriented description', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(__('Confirm your password'))
        ->assertSee(__('You are entering a secure area — please confirm your password to continue.'));
});

test('two factor challenge view uses unified heading and action-oriented description', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $response = $this->get(route('two-factor.login'))->assertOk();

    $response->assertSee(__('Two-factor authentication'));
    $response->assertSee(__('Enter the code from your authenticator app to continue.'));
    $response->assertSee(__('Use a recovery code'));
    $response->assertSee(__('Enter one of your emergency recovery codes to continue.'));
});

test('auth headings no longer use the stock Laravel secure-area phrasing', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get(route('password.confirm'))->getContent();

    expect($html)->not->toContain('This is a secure area of the application.');
});
