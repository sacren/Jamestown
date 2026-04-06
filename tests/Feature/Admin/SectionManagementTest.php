<?php

use App\Enums\DayOfWeek;
use App\Models\Course;
use App\Models\Program;
use App\Models\Room;
use App\Models\Section;
use App\Models\SectionSchedule;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view sections listing page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.sections.index'))
        ->assertOk();
});

test('admin can search sections by course name', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    $welding = Course::factory()->forProgram($program)->create(['name' => 'Introduction to Welding']);
    $hvac = Course::factory()->forProgram($program)->create(['name' => 'HVAC Fundamentals']);
    $term = Term::factory()->create();
    Section::factory()->forCourse($welding)->forTerm($term)->create();
    Section::factory()->forCourse($hvac)->forTerm($term)->create(['section_number' => '02']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->set('search', 'Welding')
        ->assertSee('Introduction to Welding')
        ->assertDontSee('HVAC Fundamentals');
});

test('admin can search sections by course code', function () {
    $admin = User::factory()->asAdmin()->create();
    $program = Program::factory()->create();
    $welding = Course::factory()->forProgram($program)->create(['name' => 'Welding', 'code' => 'WLD-101']);
    $hvac = Course::factory()->forProgram($program)->create(['name' => 'HVAC', 'code' => 'HVAC-101']);
    $term = Term::factory()->create();
    Section::factory()->forCourse($welding)->forTerm($term)->create();
    Section::factory()->forCourse($hvac)->forTerm($term)->create(['section_number' => '02']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->set('search', 'WLD-101')
        ->assertSee('Welding')
        ->assertDontSee('HVAC');
});

test('admin can filter sections by term', function () {
    $admin = User::factory()->asAdmin()->create();
    $fall = Term::factory()->create(['name' => 'Fall 2026']);
    $spring = Term::factory()->create(['name' => 'Spring 2027']);
    $program = Program::factory()->create();
    $fallCourse = Course::factory()->forProgram($program)->create(['name' => 'Fall Course']);
    $springCourse = Course::factory()->forProgram($program)->create(['name' => 'Spring Course']);
    Section::factory()->forCourse($fallCourse)->forTerm($fall)->create(['section_number' => '01']);
    Section::factory()->forCourse($springCourse)->forTerm($spring)->create(['section_number' => '01']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->set('termFilter', $fall->id)
        ->assertSee('Fall Course')
        ->assertDontSee('Spring Course');
});

test('admin can filter sections by program', function () {
    $admin = User::factory()->asAdmin()->create();
    $welding = Program::factory()->create(['name' => 'Welding']);
    $hvac = Program::factory()->create(['name' => 'HVAC']);
    $weldCourse = Course::factory()->forProgram($welding)->create(['name' => 'Welding Course']);
    $hvacCourse = Course::factory()->forProgram($hvac)->create(['name' => 'HVAC Course']);
    $term = Term::factory()->create();
    Section::factory()->forCourse($weldCourse)->forTerm($term)->create();
    Section::factory()->forCourse($hvacCourse)->forTerm($term)->create(['section_number' => '02']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->set('programFilter', $welding->id)
        ->assertSee('Welding Course')
        ->assertDontSee('HVAC Course');
});

test('admin can filter sections by instructor', function () {
    $admin = User::factory()->asAdmin()->create();
    $instructor = User::factory()->asInstructor()->create(['name' => 'John Doe']);
    $term = Term::factory()->create();
    $course = Course::factory()->create();
    Section::factory()->forCourse($course)->forTerm($term)->withInstructor($instructor)->create(['section_number' => '01']);
    Section::factory()->forTerm($term)->create(['section_number' => '02', 'instructor_id' => null]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->set('instructorFilter', $instructor->id)
        ->assertSee('John Doe');
});

test('admin can filter sections by active status', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();
    $course = Course::factory()->create(['name' => 'Active Course']);
    $course2 = Course::factory()->create(['name' => 'Inactive Course']);
    Section::factory()->forCourse($course)->forTerm($term)->create(['is_active' => true]);
    Section::factory()->forCourse($course2)->forTerm($term)->create(['is_active' => false, 'section_number' => '02']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->set('statusFilter', 'active')
        ->assertSee('Active Course')
        ->assertDontSee('Inactive Course');
});

test('admin can view create section page', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.sections.create'))
        ->assertOk();
});

test('admin can create a section with schedules', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();
    $course = Course::factory()->create();
    $room = Room::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.create')
        ->set('term_id', (string) $term->id)
        ->set('course_id', (string) $course->id)
        ->set('section_number', '01')
        ->set('max_enrollment', 25)
        ->set('schedules', [
            ['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:30', 'room_id' => (string) $room->id],
            ['day_of_week' => 'wednesday', 'start_time' => '09:00', 'end_time' => '10:30', 'room_id' => (string) $room->id],
        ])
        ->call('createSection')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.sections.index'));

    $section = Section::where('course_id', $course->id)->where('term_id', $term->id)->first();
    expect($section)->not->toBeNull();
    expect($section->section_number)->toBe('01');
    expect($section->schedules)->toHaveCount(2);
});

test('create section validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.create')
        ->set('term_id', '')
        ->set('course_id', '')
        ->set('section_number', '')
        ->set('max_enrollment', null)
        ->set('schedules', [])
        ->call('createSection')
        ->assertHasErrors(['term_id', 'course_id', 'section_number', 'max_enrollment', 'schedules']);
});

test('create section requires at least one schedule entry', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.create')
        ->set('term_id', (string) $term->id)
        ->set('course_id', (string) $course->id)
        ->set('section_number', '01')
        ->set('max_enrollment', 25)
        ->set('schedules', [])
        ->call('createSection')
        ->assertHasErrors(['schedules']);
});

test('create section validates schedule time ordering', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.create')
        ->set('term_id', (string) $term->id)
        ->set('course_id', (string) $course->id)
        ->set('section_number', '01')
        ->set('max_enrollment', 25)
        ->set('schedules', [
            ['day_of_week' => 'monday', 'start_time' => '10:30', 'end_time' => '09:00', 'room_id' => ''],
        ])
        ->call('createSection')
        ->assertHasErrors(['schedules.0.end_time']);
});

test('create section validates unique section number per course per term', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();
    $course = Course::factory()->create();
    Section::factory()->forCourse($course)->forTerm($term)->create(['section_number' => '01']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.create')
        ->set('term_id', (string) $term->id)
        ->set('course_id', (string) $course->id)
        ->set('section_number', '01')
        ->set('max_enrollment', 25)
        ->set('schedules', [
            ['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:30', 'room_id' => ''],
        ])
        ->call('createSection')
        ->assertHasErrors(['section_number']);
});

test('create section detects room schedule conflict', function () {
    $admin = User::factory()->asAdmin()->create();
    $term = Term::factory()->create();
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();
    $room = Room::factory()->create();

    // Create existing section with schedule
    $existingSection = Section::factory()->forCourse($course1)->forTerm($term)->create(['section_number' => '01']);
    SectionSchedule::factory()->forSection($existingSection)->forRoom($room)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.create')
        ->set('term_id', (string) $term->id)
        ->set('course_id', (string) $course2->id)
        ->set('section_number', '01')
        ->set('max_enrollment', 25)
        ->set('schedules', [
            ['day_of_week' => 'monday', 'start_time' => '09:30', 'end_time' => '11:00', 'room_id' => (string) $room->id],
        ])
        ->call('createSection')
        ->assertHasErrors(['schedules.0.room_id']);
});

test('create section detects instructor schedule conflict', function () {
    $admin = User::factory()->asAdmin()->create();
    $instructor = User::factory()->asInstructor()->create();
    $term = Term::factory()->create();
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();

    // Create existing section with instructor
    $existingSection = Section::factory()->forCourse($course1)->forTerm($term)->withInstructor($instructor)->create(['section_number' => '01']);
    SectionSchedule::factory()->forSection($existingSection)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.create')
        ->set('term_id', (string) $term->id)
        ->set('course_id', (string) $course2->id)
        ->set('instructor_id', (string) $instructor->id)
        ->set('section_number', '01')
        ->set('max_enrollment', 25)
        ->set('schedules', [
            ['day_of_week' => 'monday', 'start_time' => '10:00', 'end_time' => '11:00', 'room_id' => ''],
        ])
        ->call('createSection')
        ->assertHasErrors(['instructor_id']);
});

test('admin can view edit section page', function () {
    $admin = User::factory()->asAdmin()->create();
    $section = Section::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.sections.edit', $section))
        ->assertOk();
});

test('admin can update a section', function () {
    $admin = User::factory()->asAdmin()->create();
    $section = Section::factory()->create();
    SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.edit', ['section' => $section])
        ->set('max_enrollment', 30)
        ->call('updateSection')
        ->assertHasNoErrors();

    $section->refresh();
    expect($section->max_enrollment)->toBe(30);
});

test('admin can update section schedules', function () {
    $admin = User::factory()->asAdmin()->create();
    $room = Room::factory()->create();
    $section = Section::factory()->create();
    SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.edit', ['section' => $section])
        ->set('schedules', [
            ['day_of_week' => 'tuesday', 'start_time' => '14:00', 'end_time' => '15:30', 'room_id' => (string) $room->id],
            ['day_of_week' => 'thursday', 'start_time' => '14:00', 'end_time' => '15:30', 'room_id' => (string) $room->id],
        ])
        ->call('updateSection')
        ->assertHasNoErrors();

    $section->refresh();
    expect($section->schedules)->toHaveCount(2);
    expect($section->schedules->first()->day_of_week)->toBe(DayOfWeek::Tuesday);
});

test('admin can toggle section active status', function () {
    $admin = User::factory()->asAdmin()->create();
    $section = Section::factory()->create(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->call('toggleActive', $section->id);

    $section->refresh();
    expect($section->is_active)->toBeFalse();
});

test('admin can delete a section', function () {
    $admin = User::factory()->asAdmin()->create();
    $section = Section::factory()->create();
    SectionSchedule::factory()->forSection($section)->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.edit', ['section' => $section])
        ->call('deleteSection')
        ->assertRedirect(route('admin.sections.index'));

    expect(Section::find($section->id))->toBeNull();
});

test('student cannot access section management', function () {
    $student = User::factory()->asStudent()->create();

    $this->actingAs($student)
        ->get(route('admin.sections.index'))
        ->assertForbidden();
});

test('registrar can access section management', function () {
    $registrar = User::factory()->asRegistrar()->create();

    $this->actingAs($registrar)
        ->get(route('admin.sections.index'))
        ->assertOk();
});

test('guest is redirected to login from section management', function () {
    $this->get(route('admin.sections.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access section management', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.sections.index'))
        ->assertOk();
});

test('sections index shows schedule summary', function () {
    $admin = User::factory()->asAdmin()->create();
    $section = Section::factory()->create();
    SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);
    SectionSchedule::factory()->forSection($section)->create([
        'day_of_week' => DayOfWeek::Wednesday,
        'start_time' => '09:00',
        'end_time' => '10:30',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::admin.sections.index')
        ->assertSee('Mon/Wed 09:00:00-10:30:00');
});
