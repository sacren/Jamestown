<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:instructor'])->prefix('instructor')->name('instructor.')->group(function () {
    Route::livewire('sections', 'pages::instructor.sections')->name('sections');
    Route::livewire('sections/{section}/attendance', 'pages::instructor.attendance.record')->name('attendance.record');
    Route::livewire('sections/{section}/attendance/summary', 'pages::instructor.attendance.summary')->name('attendance.summary');

    // Assessments
    Route::livewire('sections/{section}/assessments', 'pages::instructor.assessments.index')->name('assessments.index');
    Route::livewire('sections/{section}/assessments/create', 'pages::instructor.assessments.create')->name('assessments.create');
    Route::livewire('sections/{section}/assessments/{assessment}/edit', 'pages::instructor.assessments.edit')->name('assessments.edit');

    // Grades
    Route::livewire('sections/{section}/grades', 'pages::instructor.grades.entry')->name('grades.entry');
    Route::livewire('sections/{section}/gradebook', 'pages::instructor.grades.gradebook')->name('grades.gradebook');
});
