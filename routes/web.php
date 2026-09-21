<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Espace public ABIWEIN
|--------------------------------------------------------------------------
| MODIFICATION : pour changer une adresse, modifiez uniquement le premier
| argument de la route concernée et conservez son nom.
*/
Route::controller(PublicSiteController::class)->group(function (): void {
    Route::get('/', 'home')->name('home');
    Route::get('/a-propos', 'about')->name('about');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/connexion', 'login')->name('login');
});

/* MODIFICATION : "5,1" autorise cinq messages par minute et par adresse IP. */
Route::post('/contact', [ContactMessageController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');
