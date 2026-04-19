<?php

use Illuminate\Support\Facades\Route;

Route::name('public.')->group(function () {
    Route::livewire('/programs', 'pages::public.programs')->name('programs');
});
