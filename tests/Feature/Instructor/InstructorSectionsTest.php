<?php

use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('instructor can view their sections page', function () {
    $instructor = User::factory()->asInstructor()->create();

    $this->actingAs($instructor)
        ->get(route('instructor.sections'))
        ->assertOk();
});

test('instructor only sees their own sections', function () {
    $instructor = User::factory()->asInstructor()->create();
    $otherInstructor = User::factory()->asInstructor()->create();

    Section::factory()->withInstructor($instructor)->create(['section_number' => '01']);
    Section::factory()->withInstructor($otherInstructor)->create(['section_number' => '99']);

    $this->actingAs($instructor);

    Livewire::test('pages::instructor.sections')
        ->assertSee('01')
        ->assertDontSee('99');
});

test('instructor can filter sections by term', function () {
    $instructor = User::factory()->asInstructor()->create();
    $fall = Term::factory()->create();
    $spring = Term::factory()->create();

    Section::factory()->withInstructor($instructor)->forTerm($fall)->create();
    Section::factory()->withInstructor($instructor)->forTerm($spring)->create();

    $this->actingAs($instructor);

    $component = Livewire::test('pages::instructor.sections')
        ->set('termFilter', (string) $fall->id);

    expect($component->instance()->sections)->toHaveCount(1);
    expect($component->instance()->sections->first()->term_id)->toBe($fall->id);
});

test('non-instructor cannot access instructor sections page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('instructor.sections'))
        ->assertForbidden();
});

test('guest is redirected to login from instructor sections', function () {
    $this->get(route('instructor.sections'))
        ->assertRedirect(route('login'));
});

test('student cannot access instructor sections page', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('instructor.sections'))
        ->assertForbidden();
});
