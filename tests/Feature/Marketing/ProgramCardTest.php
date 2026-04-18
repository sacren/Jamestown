<?php

use App\Models\Program;
use Illuminate\Support\Facades\Blade;

test('program card renders name, code, and core details', function () {
    $program = Program::factory()->create([
        'name' => 'Welding Technology',
        'code' => 'WLD',
        'description' => 'Hands-on welding training for industrial trades.',
        'duration_weeks' => 40,
        'total_credits_required' => 60,
        'tuition_cost' => 15000.00,
    ]);

    $html = Blade::render('<x-marketing.program-card :program="$program" />', compact('program'));

    expect($html)
        ->toContain('Welding Technology')
        ->toContain('WLD')
        ->toContain('Hands-on welding training')
        ->toContain('40')
        ->toContain('60')
        ->toContain('$15,000.00');
});

test('program card hides course count when not eager-loaded', function () {
    $program = Program::factory()->create();

    $html = Blade::render('<x-marketing.program-card :program="$program" />', compact('program'));

    expect($html)->not->toContain(__('Courses').':');
});

test('program card shows course count when eager-loaded', function () {
    Program::factory()->create();
    $program = Program::query()->withCount('activeCourses')->first();

    $html = Blade::render('<x-marketing.program-card :program="$program" />', compact('program'));

    expect($html)->toContain(__('Courses').':');
});

test('program card uses custom href when provided', function () {
    $program = Program::factory()->create();

    $html = Blade::render(
        '<x-marketing.program-card :program="$program" href="/custom/link" />',
        compact('program')
    );

    expect($html)
        ->toContain('/custom/link')
        ->not->toContain(route('catalog.program', $program));
});

test('program card defaults href to the authenticated catalog route', function () {
    $program = Program::factory()->create();

    $html = Blade::render('<x-marketing.program-card :program="$program" />', compact('program'));

    expect($html)->toContain(route('catalog.program', $program));
});
