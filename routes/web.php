<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'home')->name('home');
Route::livewire('/tableau-de-bord', 'watchlist.dashboard')->name('watchlist.dashboard');
Route::livewire('/recherche', 'search.index')->name('search.index');
Route::livewire('/a-venir', 'upcoming.index')->name('upcoming.index');
