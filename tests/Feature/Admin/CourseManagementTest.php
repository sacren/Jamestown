<?php

use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view courses listing page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.courses.index'))
        ->assertOk();
});

test('admin can search courses by name', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->create(['name' => 'Introduction to Welding']);
    Course::factory()->forProgram($program)->create(['name' => 'MIG Welding']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.index')
        ->set('search', 'Introduction')
        ->assertSee('Introduction to Welding')
        ->assertDontSee('MIG Welding');
});

test('admin can search courses by code', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->create(['name' => 'Introduction to Welding', 'code' => 'WLD-101']);
    Course::factory()->forProgram($program)->create(['name' => 'MIG Welding', 'code' => 'WLD-201']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.index')
        ->set('search', 'WLD-101')
        ->assertSee('Introduction to Welding')
        ->assertDontSee('MIG Welding');
});

test('admin can filter courses by program', function () {
    $admin = User::factory()->asAdmin()->create();
    $welding = Program::factory()->create(['name' => 'Welding']);
    $hvac = Program::factory()->create(['name' => 'HVAC']);
    Course::factory()->forProgram($welding)->create(['name' => 'Welding Course']);
    Course::factory()->forProgram($hvac)->create(['name' => 'HVAC Course']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.index')
        ->set('programFilter', $welding->id)
        ->assertSee('Welding Course')
        ->assertDontSee('HVAC Course');
});

test('admin can filter courses by active status', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->create(['name' => 'Active Course', 'is_active' => true]);
    Course::factory()->forProgram($program)->create(['name' => 'Inactive Course', 'is_active' => false]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.index')
        ->set('statusFilter', 'active')
        ->assertSee('Active Course')
        ->assertDontSee('Inactive Course');
});

test('admin can view create course page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.courses.create'))
        ->assertOk();
});

test('admin can create a new course', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.create')
        ->set('program_id', $program->id)
        ->set('name', 'Introduction to Welding')
        ->set('code', 'WLD-101')
        ->set('description', 'Learn the basics.')
        ->set('credit_hours', 3)
        ->set('lecture_hours', 2)
        ->set('lab_hours', 2)
        ->call('createCourse')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.courses.index'));

    $course = Course::where('code', 'WLD-101')->first();
    expect($course)->not->toBeNull();
    expect($course->name)->toBe('Introduction to Welding');
    expect($course->program_id)->toBe($program->id);
});

test('admin can create a course with prerequisites', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    $prereq = Course::factory()->forProgram($program)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.create')
        ->set('program_id', $program->id)
        ->set('name', 'Advanced Course')
        ->set('code', 'ADV-201')
        ->set('credit_hours', 4)
        ->set('lecture_hours', 2)
        ->set('lab_hours', 4)
        ->set('prerequisite_ids', [(string) $prereq->id])
        ->call('createCourse')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.courses.index'));

    $course = Course::where('code', 'ADV-201')->first();
    expect($course->prerequisites)->toHaveCount(1);
    expect($course->prerequisites->first()->id)->toBe($prereq->id);
});

test('create course validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.create')
        ->set('program_id', '')
        ->set('name', '')
        ->set('code', '')
        ->set('credit_hours', null)
        ->set('lecture_hours', null)
        ->set('lab_hours', null)
        ->call('createCourse')
        ->assertHasErrors(['program_id', 'name', 'code', 'credit_hours', 'lecture_hours', 'lab_hours']);
});

test('create course validates unique code', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->create(['code' => 'WLD-101']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.create')
        ->set('program_id', $program->id)
        ->set('name', 'Another Course')
        ->set('code', 'WLD-101')
        ->set('credit_hours', 3)
        ->set('lecture_hours', 2)
        ->set('lab_hours', 2)
        ->call('createCourse')
        ->assertHasErrors(['code']);
});

test('create course validates program exists', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.create')
        ->set('program_id', 99999)
        ->set('name', 'Test Course')
        ->set('code', 'TST-101')
        ->set('credit_hours', 3)
        ->set('lecture_hours', 2)
        ->set('lab_hours', 2)
        ->call('createCourse')
        ->assertHasErrors(['program_id']);
});

test('admin can view edit course page', function () {
    $admin = User::factory()->asAdmin()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.courses.edit', $course))
        ->assertOk();
});

test('admin can update a course', function () {
    $admin = User::factory()->asAdmin()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.edit', ['course' => $course])
        ->set('name', 'Updated Course')
        ->set('code', 'UPD-101')
        ->call('updateCourse')
        ->assertHasNoErrors();

    $course->refresh();
    expect($course->name)->toBe('Updated Course');
    expect($course->code)->toBe('UPD-101');
});

test('admin can update course prerequisites', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    $prereq1 = Course::factory()->forProgram($program)->create();
    $prereq2 = Course::factory()->forProgram($program)->create();
    $course = Course::factory()->forProgram($program)->create();
    $course->prerequisites()->attach($prereq1->id);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.edit', ['course' => $course])
        ->set('prerequisite_ids', [(string) $prereq2->id])
        ->call('updateCourse')
        ->assertHasNoErrors();

    $course->refresh();
    expect($course->prerequisites)->toHaveCount(1);
    expect($course->prerequisites->first()->id)->toBe($prereq2->id);
});

test('admin can remove all prerequisites from a course', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    $prereq = Course::factory()->forProgram($program)->create();
    $course = Course::factory()->forProgram($program)->create();
    $course->prerequisites()->attach($prereq->id);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.edit', ['course' => $course])
        ->set('prerequisite_ids', [])
        ->call('updateCourse')
        ->assertHasNoErrors();

    $course->refresh();
    expect($course->prerequisites)->toHaveCount(0);
});

test('update course validates unique code excluding self', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    Course::factory()->forProgram($program)->create(['code' => 'WLD-101']);
    $course = Course::factory()->forProgram($program)->create(['code' => 'WLD-201']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.edit', ['course' => $course])
        ->set('code', 'WLD-101')
        ->call('updateCourse')
        ->assertHasErrors(['code']);
});

test('admin can toggle course active status', function () {
    $admin = User::factory()->asAdmin()->create();
    $course = Course::factory()->create(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.index')
        ->call('toggleActive', $course->id);

    $course->refresh();
    expect($course->is_active)->toBeFalse();
});

test('admin can delete a course', function () {
    $admin = User::factory()->asAdmin()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.edit', ['course' => $course])
        ->call('deleteCourse')
        ->assertRedirect(route('admin.courses.index'));

    expect(Course::find($course->id))->toBeNull();
});

test('student cannot access course management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.courses.index'))
        ->assertForbidden();
});

test('guest is redirected to login from course management', function () {
    $this->get(route('admin.courses.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access course management', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.courses.index'))
        ->assertOk();
});

test('courses index shows program name for each course', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create(['name' => 'Welding Technology']);
    Course::factory()->forProgram($program)->create(['name' => 'Intro to Welding']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.courses.index')
        ->assertSee('Welding Technology')
        ->assertSee('Intro to Welding');
});
