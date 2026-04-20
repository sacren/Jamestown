<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::registration());
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a visitor walks the full auth flow and every Phase 6 heading survives', function () {
    Notification::fake();

    $this->get(route('register'))
        ->assertOk()
        ->assertSee(__('Create your account'))
        ->assertSee(__('Back to home'));

    $this->post(route('register.store'), [
        'name' => 'Flow Walker',
        'email' => 'flow@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'flow@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);

    $user->forceFill(['email_verified_at' => null])->save();

    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertSee(__('Check your email'))
        ->assertSee(__('We sent a verification link to your inbox. Click it to activate your account.'))
        ->assertSee(__('Back to home'));

    $this->post(route('verification.send'));
    Notification::assertSentTo($user, VerifyEmail::class);

    $verifyUrl = null;
    Notification::assertSentTo($user, VerifyEmail::class, function ($notification, $channels) use (&$verifyUrl, $user) {
        $verifyUrl = $notification->toMail($user)->actionUrl;

        return true;
    });

    $this->get($verifyUrl)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->post(route('logout'));

    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('Welcome back'))
        ->assertSee(__('Sign in with your email and password to continue.'))
        ->assertSee(__('Back to home'));

    $this->post(route('login.store'), [
        'email' => 'flow@example.com',
        'password' => 'password',
    ])->assertRedirect();
    $this->assertAuthenticatedAs($user);

    $this->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(__('Confirm your password'))
        ->assertSee(__('You are entering a secure area — please confirm your password to continue.'));

    $this->post(route('logout'));
    $this->assertGuest();

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee(__('Reset your password'))
        ->assertSee(__('Enter your email and we will send you a reset link.'))
        ->assertSee(__('Back to home'));

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->get(route('password.reset', $notification->token))
            ->assertOk()
            ->assertSee(__('Set a new password'))
            ->assertSee(__('Choose a new password for your account.'));

        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login', absolute: false));

        return true;
    });

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});
