<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:student'])->prefix('registration')->name('registration.')->group(function () {
    Route::livewire('/', 'pages::registration.sections')->name('sections');
    Route::livewire('/schedule', 'pages::registration.schedule')->name('schedule');
    Route::livewire('/attendance', 'pages::registration.attendance')->name('attendance');
    Route::livewire('/grades', 'pages::registration.grades')->name('grades');
});
