<?php

use App\Models\Program;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    if (File::exists(public_path('sitemap.xml'))) {
        File::delete(public_path('sitemap.xml'));
    }
});

afterEach(function () {
    if (File::exists(public_path('sitemap.xml'))) {
        File::delete(public_path('sitemap.xml'));
    }
});

test('sitemap:generate writes public/sitemap.xml and reports the count', function () {
    $this->artisan('sitemap:generate')
        ->expectsOutputToContain('Wrote')
        ->assertSuccessful();

    expect(File::exists(public_path('sitemap.xml')))->toBeTrue();
});

test('sitemap contains the xml declaration and sitemaps.org urlset namespace', function () {
    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = File::get(public_path('sitemap.xml'));

    expect($xml)
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->toContain('</urlset>');
});

test('sitemap lists every static public marketing route', function () {
    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = File::get(public_path('sitemap.xml'));

    expect($xml)
        ->toContain('<loc>'.route('home').'</loc>')
        ->toContain('<loc>'.route('public.programs').'</loc>')
        ->toContain('<loc>'.route('about').'</loc>')
        ->toContain('<loc>'.route('contact').'</loc>')
        ->toContain('<loc>'.route('privacy').'</loc>')
        ->toContain('<loc>'.route('terms').'</loc>');
});

test('sitemap includes each active program by slug and a lastmod timestamp', function () {
    $active = Program::factory()->create([
        'name' => 'Welding Technology',
        'is_active' => true,
    ]);

    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = File::get(public_path('sitemap.xml'));

    expect($xml)
        ->toContain('<loc>'.route('public.program', $active).'</loc>')
        ->toContain('<lastmod>'.$active->updated_at->toIso8601String().'</lastmod>');
});

test('sitemap excludes inactive programs', function () {
    $active = Program::factory()->create([
        'name' => 'Welding Technology',
        'is_active' => true,
    ]);
    $retired = Program::factory()->create([
        'name' => 'Retired Program',
        'is_active' => false,
    ]);

    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = File::get(public_path('sitemap.xml'));

    expect($xml)
        ->toContain($active->slug)
        ->not->toContain($retired->slug);
});

test('sitemap does not include auth-gated or admin routes', function () {
    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = File::get(public_path('sitemap.xml'));

    expect($xml)
        ->not->toContain('/dashboard')
        ->not->toContain('/admin')
        ->not->toContain('/instructor')
        ->not->toContain('/settings')
        ->not->toContain('/registration');
});

test('sitemap assigns the home page the highest priority', function () {
    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = File::get(public_path('sitemap.xml'));
    $homeBlock = Str::between(
        $xml,
        '<loc>'.route('home').'</loc>',
        '</url>',
    );

    expect($homeBlock)->toContain('<priority>1.0</priority>');
});

test('sitemap:generate is scheduled to run daily', function () {
    $schedule = app(Schedule::class);

    $matches = collect($schedule->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'sitemap:generate'));

    expect($matches)->not->toBeEmpty();
    expect($matches->first()->expression)->toBe('0 0 * * *');
});
