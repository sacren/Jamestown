<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('catalog')->name('catalog.')->group(function () {
    Route::livewire('/', 'pages::catalog.programs')->name('programs');
    Route::livewire('/programs/{program}', 'pages::catalog.program-detail')->name('program');
});
