<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('announcement belongs to an author', function () {
    $author = User::factory()->create();
    $announcement = Announcement::factory()->forAuthor($author)->create();

    expect($announcement->author->id)->toBe($author->id);
});

test('published scope returns only published announcements', function () {
    Announcement::factory()->create(['published_at' => now()->subDay()]);
    Announcement::factory()->draft()->create();
    Announcement::factory()->scheduled()->create();

    $published = Announcement::published()->get();

    expect($published)->toHaveCount(1);
});

test('published scope excludes soft-deleted announcements', function () {
    $announcement = Announcement::factory()->create(['published_at' => now()->subDay()]);
    $announcement->delete();

    expect(Announcement::published()->count())->toBe(0);
});

test('forAudience scope returns all and matching role announcements for student', function () {
    Announcement::factory()->forAudience(AnnouncementAudience::All)->create();
    Announcement::factory()->forAudience(AnnouncementAudience::Students)->create();
    Announcement::factory()->forAudience(AnnouncementAudience::Instructors)->create();

    $results = Announcement::forAudience('students')->get();

    expect($results)->toHaveCount(2);
});

test('forAudience scope returns all and matching role announcements for instructor', function () {
    Announcement::factory()->forAudience(AnnouncementAudience::All)->create();
    Announcement::factory()->forAudience(AnnouncementAudience::Students)->create();
    Announcement::factory()->forAudience(AnnouncementAudience::Instructors)->create();

    $results = Announcement::forAudience('instructors')->get();

    expect($results)->toHaveCount(2);
});

test('forAudience scope does not return instructor announcements for student', function () {
    Announcement::factory()->forAudience(AnnouncementAudience::Instructors)->create();

    expect(Announcement::forAudience('students')->count())->toBe(0);
});

test('isPublished returns true for past published_at', function () {
    $announcement = Announcement::factory()->create(['published_at' => now()->subHour()]);

    expect($announcement->isPublished())->toBeTrue();
});

test('isPublished returns false for null published_at', function () {
    $announcement = Announcement::factory()->draft()->create();

    expect($announcement->isPublished())->toBeFalse();
});

test('isPublished returns false for future published_at', function () {
    $announcement = Announcement::factory()->scheduled()->create();

    expect($announcement->isPublished())->toBeFalse();
});

test('isDraft returns true only when published_at is null', function () {
    $draft = Announcement::factory()->draft()->create();
    $published = Announcement::factory()->create();

    expect($draft->isDraft())->toBeTrue();
    expect($published->isDraft())->toBeFalse();
});

test('isScheduled returns true only for future published_at', function () {
    $scheduled = Announcement::factory()->scheduled()->create();
    $published = Announcement::factory()->create(['published_at' => now()->subHour()]);
    $draft = Announcement::factory()->draft()->create();

    expect($scheduled->isScheduled())->toBeTrue();
    expect($published->isScheduled())->toBeFalse();
    expect($draft->isScheduled())->toBeFalse();
});

test('factory creates valid announcement with all states', function () {
    $published = Announcement::factory()->create();
    $draft = Announcement::factory()->draft()->create();
    $scheduled = Announcement::factory()->scheduled()->create();

    expect($published->id)->toBeGreaterThan(0);
    expect($draft->published_at)->toBeNull();
    expect($scheduled->published_at->isFuture())->toBeTrue();
});

test('soft delete works and announcement is restorable', function () {
    $announcement = Announcement::factory()->create();
    $announcement->delete();

    expect(Announcement::count())->toBe(0);
    expect(Announcement::withTrashed()->count())->toBe(1);

    $announcement->restore();
    expect(Announcement::count())->toBe(1);
});

test('user has many announcements', function () {
    $author = User::factory()->create();
    Announcement::factory()->forAuthor($author)->count(3)->create();

    expect($author->announcements)->toHaveCount(3);
});
