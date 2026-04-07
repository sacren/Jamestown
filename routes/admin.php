<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super-admin|admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/users');

    Route::livewire('users', 'pages::admin.users.index')->name('users.index');
    Route::livewire('users/create', 'pages::admin.users.create')->name('users.create');
    Route::livewire('users/{user}/edit', 'pages::admin.users.edit')->name('users.edit');

    // Programs
    Route::livewire('programs', 'pages::admin.programs.index')->name('programs.index');
    Route::livewire('programs/create', 'pages::admin.programs.create')->name('programs.create');
    Route::livewire('programs/{program}/edit', 'pages::admin.programs.edit')->name('programs.edit');

    // Courses
    Route::livewire('courses', 'pages::admin.courses.index')->name('courses.index');
    Route::livewire('courses/create', 'pages::admin.courses.create')->name('courses.create');
    Route::livewire('courses/{course}/edit', 'pages::admin.courses.edit')->name('courses.edit');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Terms
    Route::middleware('permission:manage-terms')->group(function () {
        Route::livewire('terms', 'pages::admin.terms.index')->name('terms.index');
        Route::livewire('terms/create', 'pages::admin.terms.create')->name('terms.create');
        Route::livewire('terms/{term}/edit', 'pages::admin.terms.edit')->name('terms.edit');
    });

    // Rooms
    Route::middleware('permission:manage-rooms')->group(function () {
        Route::livewire('rooms', 'pages::admin.rooms.index')->name('rooms.index');
        Route::livewire('rooms/create', 'pages::admin.rooms.create')->name('rooms.create');
        Route::livewire('rooms/{room}/edit', 'pages::admin.rooms.edit')->name('rooms.edit');
    });

    // Sections
    Route::middleware('permission:manage-sections')->group(function () {
        Route::livewire('sections', 'pages::admin.sections.index')->name('sections.index');
        Route::livewire('sections/create', 'pages::admin.sections.create')->name('sections.create');
        Route::livewire('sections/{section}/edit', 'pages::admin.sections.edit')->name('sections.edit');
    });

    // Enrollments
    Route::middleware('permission:manage-enrollments')->group(function () {
        Route::livewire('enrollments', 'pages::admin.enrollments.index')->name('enrollments.index');
        Route::livewire('enrollments/create', 'pages::admin.enrollments.create')->name('enrollments.create');
    });
});
