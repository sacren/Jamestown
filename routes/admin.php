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
