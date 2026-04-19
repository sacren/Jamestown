<?php

use Illuminate\Support\Facades\Route;

Route::name('public.')->group(function () {
    Route::livewire('/programs', 'pages::public.programs')->name('programs');
    Route::livewire('/programs/{publicProgram:slug}', 'pages::public.program')->name('program');
});
