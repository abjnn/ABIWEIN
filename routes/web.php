<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
| La limitation réduit les essais répétés ; aucune route d’inscription
| publique n’est définie dans ABIWEIN.
*/
Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('login.store');

Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/* Toutes les pages ci-dessous exigent une session et un compte encore actif. */
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    /* L’administration des comptes reste strictement réservée aux administrateurs. */
    Route::prefix('administration')->name('admin.')->middleware('role:administrator')->group(function (): void {
        Route::get('/utilisateurs', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/utilisateurs/creer', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/utilisateurs', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/utilisateurs/{user}/statut', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});

/* Les modules métier conservent leurs routes dans des fichiers séparés. */
require __DIR__.'/commerce.php';
require __DIR__.'/health.php';
require __DIR__.'/reports.php';
require __DIR__.'/stocks.php';
