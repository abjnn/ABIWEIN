<?php

use App\Http\Controllers\Flock\DailyRecordController;
use App\Http\Controllers\Flock\FlockActivityController;
use App\Http\Controllers\Flock\FlockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bandes et suivi quotidien ABIWEIN
|--------------------------------------------------------------------------
| L'administrateur et le vétérinaire consultent le registre. Le responsable
| d'exploitation réalise seul les écritures et le suivi quotidien.
*/

// La liste technique reste consultative pour l'administrateur et le vétérinaire.
Route::middleware(['auth', 'active', 'role:administrator,farm_manager,veterinarian'])
    ->prefix('bandes')
    ->name('flocks.')
    ->group(function (): void {
        Route::get('/', [FlockController::class, 'index'])->name('index');
    });

// Toutes les routes d'écriture restent strictement réservées au responsable.
Route::middleware(['auth', 'active', 'role:farm_manager'])
    ->prefix('bandes')
    ->name('flocks.')
    ->group(function (): void {
        // Les URL fixes précèdent la fiche paramétrée pour éviter toute ambiguïté.
        Route::get('/creer', [FlockController::class, 'create'])->name('create');
        Route::post('/', [FlockController::class, 'store'])->name('store');

        Route::get('/{flock}/modifier', [FlockController::class, 'edit'])->whereNumber('flock')->name('edit');
        Route::put('/{flock}', [FlockController::class, 'update'])->whereNumber('flock')->name('update');
        Route::post('/{flock}/transferts', [FlockController::class, 'transfer'])->whereNumber('flock')->name('transfer');
        Route::patch('/{flock}/cloture', [FlockController::class, 'close'])->whereNumber('flock')->name('close');
        Route::patch('/{flock}/archivage', [FlockController::class, 'archive'])->whereNumber('flock')->name('archive');

        Route::get('/{flock}/releves/nouveau', [DailyRecordController::class, 'create'])
            ->whereNumber('flock')
            ->name('daily-records.create');
        Route::post('/{flock}/releves', [DailyRecordController::class, 'store'])
            ->whereNumber('flock')
            ->name('daily-records.store');
        Route::get('/{flock}/releves/{dailyRecord}/modifier', [DailyRecordController::class, 'edit'])
            ->whereNumber(['flock', 'dailyRecord'])
            ->name('daily-records.edit');
        Route::put('/{flock}/releves/{dailyRecord}', [DailyRecordController::class, 'update'])
            ->whereNumber(['flock', 'dailyRecord'])
            ->name('daily-records.update');

        Route::post('/{flock}/activites', [FlockActivityController::class, 'store'])
            ->whereNumber('flock')
            ->name('activities.store');
    });

// La fiche détaillée est déclarée après « creer » pour ne jamais capturer ce mot.
Route::middleware(['auth', 'active', 'role:administrator,farm_manager,veterinarian'])
    ->prefix('bandes')
    ->name('flocks.')
    ->group(function (): void {
        Route::get('/{flock}', [FlockController::class, 'show'])->whereNumber('flock')->name('show');
    });
