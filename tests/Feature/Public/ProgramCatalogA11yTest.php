<?php

use App\Models\Program;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('public catalog page has a single h1 for the page heading', function () {
    Program::factory()->count(2)->create();

    $html = $this->get(route('public.programs'))->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toContain(__('Programs that build careers'));
});

test('public catalog page renders program card names as h2 headings', function () {
    Program::factory()->create(['name' => 'Welding Technology']);

    $html = $this->get(route('public.programs'))->assertOk()->getContent();

    expect($html)->toMatch('/<h2[^>]*>\s*Welding Technology/');
});

test('public catalog page includes skip link and main landmark', function () {
    $html = $this->get(route('public.programs'))->assertOk()->getContent();

    expect($html)
        ->toContain(__('Skip to main content'))
        ->toContain('<main id="main"');
});

test('public catalog page form controls are labeled for assistive tech', function () {
    $html = $this->get(route('public.programs'))->assertOk()->getContent();

    expect($html)
        ->toContain(__('Search'))
        ->toContain(__('Duration'))
        ->toContain(__('Tuition'));
});

test('public program detail page has a single h1 for the program name', function () {
    $program = Program::factory()->create(['name' => 'Welding Technology']);

    $html = $this->get(route('public.program', $program))->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toMatch('/<h1[^>]*>\s*Welding Technology/');
});

test('public program detail page renders courses heading as h2', function () {
    $program = Program::factory()->create();

    $html = $this->get(route('public.program', $program))->assertOk()->getContent();

    expect($html)->toMatch('/<h2[^>]*>\s*'.preg_quote(__('Courses in this program'), '/').'/');
});

test('public program detail page wraps breadcrumbs in nav with aria label', function () {
    $program = Program::factory()->create();

    $this->get(route('public.program', $program))
        ->assertOk()
        ->assertSee('aria-label="'.__('Breadcrumb').'"', false);
});
