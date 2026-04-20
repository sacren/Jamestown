<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/about', 'pages::about')->name('about');
Route::livewire('/privacy', 'pages::privacy')->name('privacy');
Route::livewire('/terms', 'pages::terms')->name('terms');
Route::livewire('/contact', 'pages::contact')->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('notifications', 'pages::notifications')->name('notifications');
});

require __DIR__.'/settings.php';
