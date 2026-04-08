<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:instructor'])->prefix('instructor')->name('instructor.')->group(function () {
    Route::livewire('sections', 'pages::instructor.sections')->name('sections');
    Route::livewire('sections/{section}/attendance', 'pages::instructor.attendance.record')->name('attendance.record');
    Route::livewire('sections/{section}/attendance/summary', 'pages::instructor.attendance.summary')->name('attendance.summary');
});
